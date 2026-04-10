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

	public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
	{
		$value = isset($items[$delta]->value) ? $items[$delta]->value : '';


		$element += [
			'#type' => 'textfield',
			'#size' => 24,
			'#default_value' => $value,
			'#autocomplete_route_name' => 'digitalia_muni_autocomplete_remote_ror.autocomplete',
		];

		return ['value' => $element];
	}
}
