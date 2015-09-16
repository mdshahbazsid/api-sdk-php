<?php
namespace Hitmeister\Component\Api\Transfers;

/**
 * This class was auto generated. Please, do not modify it!
 *
 * @codeCoverageIgnore
 *
 * @property string $callback_url
 * @property string $fallback_email
 * @property string $event_name
 *
 *
 */
class SubscriptionAddTransfer extends AbstractTransfer
{
    /**
     * @return array
     */
    public function getProperties()
    {
        static $properties = array (
  'callback_url' => 
  array (
    'embedded' => false,
    'is_multiple' => false,
  ),
  'fallback_email' => 
  array (
    'embedded' => false,
    'is_multiple' => false,
  ),
  'event_name' => 
  array (
    'embedded' => false,
    'is_multiple' => false,
  ),
);
        return $properties;
    }
}
