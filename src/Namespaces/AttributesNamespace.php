<?php

namespace Hitmeister\Component\Api\Namespaces;

use Hitmeister\Component\Api\Endpoints\Attributes\Get;
use Hitmeister\Component\Api\Exceptions\ResourceNotFoundException;
use Hitmeister\Component\Api\Helper\Response;
use Hitmeister\Component\Api\Transfers\AttributeTransfer;

/**
 * Class AttributesNamespace
 *
 * @category PHP-SDK
 * @package  Hitmeister\Component\Api\Namespaces
 * @author   Maksim Naumov <maksim.naumov@hitmeister.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://www.hitmeister.de/api/v1/
 */
class AttributesNamespace extends AbstractNamespace
{
	/**
	 * @param int $id
	 * @return AttributeTransfer|null
	 */
	public function get($id)
	{
		$endpoint = new Get($this->getTransport());
		$endpoint->setId($id);

		try {
			$result = $endpoint->performRequest();
		} catch(ResourceNotFoundException $e) {
			return null;
		}

		Response::checkBody($result, $endpoint);
		return AttributeTransfer::make($result['json']);
	}
}