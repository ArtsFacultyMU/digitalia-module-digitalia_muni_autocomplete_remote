<?php

namespace Drupal\digitalia_muni_autocomplete_remote\Controller;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Class JsonApiRORController
 * @package Drupal\digitalia_muni_autocomplete_remote\Controller
 */
class JsonApiRORController
{

  /**
   * @return JsonResponse
   */
  public function handleAutocomplete(Request $request)
  {
	$results = [];
	$input = $request->query->get("q");
	if (!$input) {
	  return new JsonResponse($results);
	}

	$input = Xss::filter($input);

	try {
		$client_factory = \Drupal::service("http_client_factory");
		$client = $client_factory->fromOptions([
			"verify" => FALSE,
			"headers" => [
				"Content-type" => "application/json",
				"Accept" => "application/json",
			],
		]);

		$response = $client->request("GET", "https://api.ror.org/v2/organizations?query={$input}");
		$result = $response->getBody()->getContents();
		$decoded = json_decode($result, TRUE);

		foreach ($decoded["items"] as $child) {
			$label = array();
			array_push($label, $child["id"]);

			// get exactly one display name
			foreach ($child["names"] as $name) {
				if (in_array("ror_display", $name["types"])) {
					array_push($label, $name["value"]);
					break;
				}
			}

			if (!empty($child["links"])) {
				array_push($label, $child["links"][0]["value"]);
			}

			array_push($label, implode(", ", $child["types"]));
			array_push($label, $child["status"]);
			array_push($label, $child["locations"][0]["geonames_details"]["country_name"]);
			array_push($label, $child["locations"][0]["geonames_details"]["name"]);

			\Drupal::logger("ror")->error(print_r(json_encode($child, TRUE), TRUE));

			$results[] = [
				//"value" => $child["id"],
				"value" => json_encode($child),
				"label" => implode(" | ", array_filter($label)),
			];
		}

	} catch (ClientException $e) {
		\Drupal::logger("ror")->error(print_r($e, TRUE));
		return new JsonResponse($results);
	}

	return new JsonResponse($results);
  }

}

