<?php

namespace Drupal\markdown\BcSupport;

/**
 * Ensures that TrustedCallbackInterface can be enforced for callback methods.
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\Core\Security\TrustedCallbackInterface instead.
 * @see https://www.drupal.org/project/markdown/issues/3103679
 * @see \Drupal\markdown\BcSupport\TrustedCallbackInterface
 */
trait DoTrustedCallbackTrait {

  /**
   * Performs a callback.
   *
   * If the callback is trusted the callback will occur. Trusted callbacks must
   * be methods of a class that implements
   * \Drupal\markdown\BcSupport\TrustedCallbackInterface or $extra_trusted_interface
   * or be an anonymous function. If the callback is not trusted then whether or
   * not the callback is called and what type of error is thrown depends on
   * $error_type. To provide time for dependent code to use trusted callbacks
   * use TrustedCallbackInterface::TRIGGER_SILENCED_DEPRECATION and then at a
   * later date change this to TrustedCallbackInterface::THROW_EXCEPTION.
   *
   * @param callable $callback
   *   The callback to call. Note that callbacks which are objects and use the
   *   magic method __invoke() are not supported.
   * @param array $args
   *   The arguments to pass the callback.
   * @param $message
   *   The error message if the callback is not trusted. If the message contains
   *   "%s" it will be replaced in with the resolved callback.
   * @param string $error_type
   *   (optional) The type of error to trigger. One of:
   *   - TrustedCallbackInterface::THROW_EXCEPTION
   *   - TrustedCallbackInterface::TRIGGER_DEPRECATION
   *   - TrustedCallbackInterface::TRIGGER_SILENCED_DEPRECATION
   *   Defaults to TrustedCallbackInterface::THROW_EXCEPTION.
   * @param string $extra_trusted_interface
   *   (optional) An additional interface that if implemented by the callback
   *   object means any public methods on that object are trusted.
   *
   * @return mixed
   *   The callback's return value.
   *
   * @throws \Drupal\markdown\BcSupport\UntrustedCallbackException
   *   Exception thrown if the callback is not trusted and $error_type equals
   *   TrustedCallbackInterface::THROW_EXCEPTION.
   *
   * @see \Drupal\markdown\BcSupport\TrustedCallbackInterface
   */
  public function doTrustedCallback(callable $callback, array $args, $message, $error_type = TrustedCallbackInterface::THROW_EXCEPTION, $extra_trusted_interface = NULL) {
    $object_or_classname = $callback;
    $safe_callback = FALSE;

    if (is_array($callback)) {
      list($object_or_classname, $method_name) = $callback;
    }
    elseif (is_string($callback) && strpos($callback, '::') !== FALSE) {
      list($object_or_classname, $method_name) = explode('::', $callback, 2);
    }

    if (isset($method_name)) {
      if ($extra_trusted_interface && is_subclass_of($object_or_classname, $extra_trusted_interface)) {
        $safe_callback = TRUE;
      }
      elseif (is_subclass_of($object_or_classname, TrustedCallbackInterface::class)) {
        if (is_object($object_or_classname)) {
          $methods = $object_or_classname->trustedCallbacks();
        }
        else {
          $methods = call_user_func($object_or_classname . '::trustedCallbacks');
        }
        $safe_callback = in_array($method_name, $methods, TRUE);
      }
    }
    elseif ($callback instanceof \Closure) {
      $safe_callback = TRUE;
    }

    if (!$safe_callback) {
      $description = $object_or_classname;
      if (is_object($description)) {
        $description = get_class($description);
      }
      if (isset($method_name)) {
        $description .= '::' . $method_name;
      }
      $message = sprintf($message, $description);
      if ($error_type === TrustedCallbackInterface::TRIGGER_SILENCED_DEPRECATION) {
        @trigger_error($message, E_USER_DEPRECATED);
      }
      elseif ($error_type === TrustedCallbackInterface::TRIGGER_WARNING) {
        trigger_error($message, E_USER_WARNING);
      }
      else {
        throw new UntrustedCallbackException($message);
      }
    }

    return call_user_func_array($callback, $args);
  }

}
