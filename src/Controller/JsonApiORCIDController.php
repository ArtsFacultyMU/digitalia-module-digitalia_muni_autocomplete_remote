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
 * Class JsonApiORCIDController
 * @package Drupal\digitalia_muni_autocomplete_remote\Controller
 */
class JsonApiORCIDController
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
		$response = $client->request("GET", "https://pub.orcid.org/v3.0/expanded-search/?q={$input}&start=0&rows=10");
		$result = $response->getBody()->getContents();
		$decoded = json_decode($result, TRUE);

		foreach ($decoded["expanded-result"] as $child) {
			$label = array();
			array_push($label, $child["orcid-id"]);
			array_push($label, $child["family-names"] . ", " . $child["given-names"]);
			array_push($label, implode(", ", $child["institution-name"]));
			array_push($label, implode(", ", $child["email"]));

			$results[] = [
				"value" => $child["orcid-id"],
				"label" => implode(" | ", array_filter($label)),
				// pass the whole result, so other fields can be populated
				// this is a stupid solution, but I could not come up with a better one
				// and making another http request to orcid.org seems even more stupid
				//"value" => json_encode($child),
			];
		}

	} catch (ClientException $e) {
		\Drupal::logger("orcid")->error(print_r($e, TRUE));
		return new JsonResponse($results);
	}

	return new JsonResponse($results);
  }

}
