<?php

namespace Hitmeister\Component\Api;

use Hitmeister\Component\Api\Endpoints\AbstractEndpoint;
use Hitmeister\Component\Api\Helper\Response;
use Hitmeister\Component\Api\Transfers\AbstractTransfer;

class Cursor implements \Iterator
{
	const MAX_API_LIMIT = 30; // actually it is not max limit but minimal of maximals

	/** @var AbstractEndpoint */
	private $endpoint;

	/** @var int */
	private $userLimit;

	/** @var int */
	private $userOffset = 0;

	/** @var int */
	private $apiLimit = Cursor::MAX_API_LIMIT;

	/** @var int */
	private $apiOffset = 0;

	/** @var array */
	private $apiParams = [];

	/** @var bool */
	private $apiHasNext = true;

	/** @var int */
	private $position = 0;

	/** @var array */
	private $rawData = [];

	/** @var array */
	private $transferData = [];

	/** @var string */
	private $transferClass;

	/**
	 * @param AbstractEndpoint $endpoint
	 * @param string           $transferClass
	 */
	public function __construct(AbstractEndpoint $endpoint, $transferClass)
	{
		$this->transferClass = $transferClass;
		$this->endpoint = $endpoint;
		$this->apiParams = $this->endpoint->getParams();

		if (isset($this->apiParams['offset'])) {
			$this->userOffset = (int)$this->apiParams['offset'];
			$this->apiOffset = $this->userOffset;
		}

		if (isset($this->apiParams['limit'])) {
			$this->userLimit = (int)$this->apiParams['limit'];
			$this->apiLimit = $this->userLimit;

			// Not that much
			if ($this->apiLimit > Cursor::MAX_API_LIMIT) {
				$this->apiLimit = Cursor::MAX_API_LIMIT;
			}
		}
	}

	/**
	 * @return AbstractEndpoint
	 */
	public function getEndpoint()
	{
		return $this->endpoint;
	}

	/**
	 * {@inheritdoc}
	 */
	public function current()
	{
		return $this->getCurrent();
	}

	/**
	 * {@inheritdoc}
	 */
	public function next()
	{
		++$this->position;
	}

	/**
	 * {@inheritdoc}
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * {@inheritdoc}
	 */
	public function valid()
	{
		if (!isset($this->rawData[$this->position])) {
			$this->fetchData();
		}
		return isset($this->rawData[$this->position]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function rewind()
	{
		$this->position = 0;
	}

	/**
	 *
	 */
	private function fetchData()
	{
		if (!$this->apiHasNext) {
			return;
		}

		// Set limits defined by API
		$this->apiParams['limit'] = $this->apiLimit;
		$this->apiParams['offset'] = $this->apiOffset;

		// Adjust limits by user
		if (null !== $this->userLimit) {
			$expectedCount = $this->userOffset + $this->userLimit;
			$newCount = $this->apiLimit + $this->apiOffset;
			if ($newCount > $expectedCount) {
				$dx = $newCount - $expectedCount;
				$this->apiParams['limit'] -= $dx;
			}
		}

		$this->endpoint->setParams($this->apiParams);
		$resultRequest = $this->endpoint->performRequest();

		Response::checkBody($resultRequest);
		$cursorData = Response::extractCursorPosition($resultRequest);

		// The end
		if (
			// Has no more items
			$cursorData['end'] == $cursorData['total'] ||
			// User's defined
			(
				null !== $this->userOffset &&
				null !== $this->userLimit &&
				$cursorData['end'] >= ($this->userOffset + $this->userLimit)
			) ||
			0 === count($resultRequest['json'])
		) {
			$this->apiHasNext = false;
		} else {
			$this->apiOffset += $this->apiLimit;
		}

		$this->rawData = array_merge($this->rawData, $resultRequest['json']);
	}

	/**
	 * @return AbstractTransfer|null
	 */
	private function getCurrent()
	{
		// Check transfer first
		if (isset($this->transferData[$this->position])) {
			return $this->transferData[$this->position];
		}

		// Check raw data next, build transfer
		if (isset($this->rawData[$this->position])) {
			$this->transferData[$this->position] =
				AbstractTransfer::makeTransfer($this->transferClass, $this->rawData[$this->position]);
			return $this->transferData[$this->position];
		}

		return null;
	}
}