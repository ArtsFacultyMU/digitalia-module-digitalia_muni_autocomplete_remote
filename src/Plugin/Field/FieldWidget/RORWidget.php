<?php

namespace Drupal\digitalia_muni_autocomplete_remote\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 *
 */
#[FieldWidget(
	id: 'ror_widget',
	label: new TranslatableMarkup('Simple remote autocomplete ROR'),
	field_types: [
		'string',
	],
)]
class RORWidget extends WidgetBase
{
	protected $machine_name;
	protected $base_form_data_selector;
	protected $element;

	public static function defaultSettings()
	{
		return [
			"org_name" => "field_corporate_body_name",
		] + parent::defaultSettings();
	}

	public function settingsForm(array $form, FormStateInterface $form_state)
	{
		$element["org_name"] = [
			"#type" => "textfield",
			"#title" => $this->t("Organization name field machine name"),
			"#default_value" => $this->getSetting("org_name"),
			"#required" => FALSE,
		];

		return $element;
	}

	public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
	{
		$value = isset($items[$delta]->value) ? $items[$delta]->value : '';

		// necessary only for populating other fields
		$this->machine_name = $items->getName();
		$this->base_form_data_selector = $form["#attributes"]["data-drupal-selector"];
		$this->element = $element;

		$element += [
			'#type' => 'textfield',
			'#size' => 24,
			'#default_value' => $value,
			'#autocomplete_route_name' => 'digitalia_muni_autocomplete_remote_ror.autocomplete',
			"#ajax" => [
				"callback" => [$this, "populateFields"],
				"event" => "autocompleteclose change",
			],
			"#maxlength" => 4096,
		];

		return ['value' => $element];
	}

	/**
	 * I hate this solution. As passing information through field value is so far only
	 * solution known to me, uncommenting '#ajax' section above and altering Controller is necessary, if desired.
	 * Simply overwrites fields regardless of their contents.
	 */
	public function populateFields(array &$form, FormStateInterface $form_state)
	{
		$response = new AjaxResponse();
		$clean_values = $form_state->cleanValues()->getValues();

		$element_tree = $this->element["#field_parents"];

		$current_element = $clean_values;

		$org_name_html_selector = str_replace("_", "-", $this->getSetting("org_name"));
		$ror_html_selector = str_replace("_", "-", $this->machine_name);

		while (!empty($element_tree)) {
			\Drupal::logger("DEBUG_WIDGET_TRAVERSING_ELEMENT")->debug(print_r($current_element, TRUE));
			$current_element = $current_element[array_shift($element_tree)];
		}


		$json = $current_element[$this->machine_name];
		$decoded = json_decode($json[0]["value"], TRUE);

		$response->addCommand(new InvokeCommand("[data-drupal-selector={$this->base_form_data_selector}-{$ror_html_selector}-0-value]", "val", [$decoded["id"]]));


		if (empty($org_name_html_selector)) {
			return $response;
		}


		if (empty($decoded)) {
			// enable name fields
			$response->addCommand(new InvokeCommand("[data-drupal-selector={$this->base_form_data_selector}-{$org_name_html_selector}-0-value]", "removeAttr", ["readonly"]));
			return $response;
		}

		$display_name = "";

		foreach ($decoded["names"] as $name) {
			if (in_array("ror_display", $name["types"])) {
				$display_name = $name["value"];
				break;
			}

		}

		// too hardwired for my taste, multivalue fields would probably be even worse
		// ID has a mangled string appended, so data-drupal-selector is used
		$response->addCommand(new InvokeCommand("[data-drupal-selector={$this->base_form_data_selector}-{$org_name_html_selector}-0-value]", "val", [$display_name]));
		$response->addCommand(new InvokeCommand("[data-drupal-selector={$this->base_form_data_selector}-{$org_name_html_selector}-0-value]", "attr", ["readonly","readonly"]));

		// disable name fields
		//\Drupal::logger("DEBUG")->debug(print_r($form, TRUE));

		return $response;
	}
}
