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
	id: 'orcid_widget',
	label: new TranslatableMarkup('Simple remote autocomplete ORCID'),
	field_types: [
		'string',
	],
)]
class ORCIDWidget extends WidgetBase
{
	protected $machine_name;

	public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
	{
		$value = isset($items[$delta]->value) ? $items[$delta]->value : '';

		// necessary only for populating other fields
		$this->machine_name = $items->getName();

		$element += [
			'#type' => 'textfield',
			'#size' => 24,
			'#default_value' => $value,
			'#autocomplete_route_name' => 'digitalia_muni_autocomplete_remote_orcid.autocomplete',
//			'#ajax' => [
//				'callback' => [$this, 'populateFields'],
//				'event' => 'autocompleteclose',
//			],
//			'#maxlength' => 256,
		];

		return ['value' => $element];
	}

	/**
	 * Too hardwired, but can be done. As passing information through field value is so far only
	 * solution known to me, uncommenting '#ajax' section above and altering Controller is necessary, if desired.
	 * Simply overwrites fields regardless of their contents.
	 */
	public function populateFields(array &$form, FormStateInterface $form_state)
	{
		$response = new AjaxResponse();

		$json = $form_state->getValue($this->machine_name);
		$decoded = json_decode($json[0]["value"], TRUE);

		if (empty($decoded)) {
			return $response;
		}

		// too hardwired for my taste, multivalue fields would probably be even worse
		$response->addCommand(new InvokeCommand('#edit-field-first-names-0-value', 'val', [$decoded["given-names"]]));
		$response->addCommand(new InvokeCommand('#edit-field-last-names-0-value', 'val', [$decoded["family-names"]]));
		$response->addCommand(new InvokeCommand('#edit-field-orcid-0-value', 'val', [$decoded["orcid-id"]]));

		return $response;
	}


}
