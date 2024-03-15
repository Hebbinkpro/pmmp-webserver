<?php

namespace Hebbinkpro\WebServer\libs\Laravel\SerializableClosure;

use Closure;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Contracts\Serializable;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\InvalidSignatureException;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Serializers\Native;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Serializers\Signed;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Signers\Hmac;

class SerializableClosure
{
    /**
     * The closure's serializable.
     *
     * @var Serializable
     */
    protected $serializable;

    /**
     * Creates a new serializable closure instance.
     *
     * @param Closure $closure
     * @return void
     */
    public function __construct(Closure $closure)
    {
        $this->serializable = Signed::$signer
            ? new Signed($closure)
            : new Native($closure);
    }

    /**
     * Create a new unsigned serializable closure instance.
     *
     * @param Closure $closure
     * @return UnsignedSerializableClosure
     */
    public static function unsigned(Closure $closure)
    {
        return new UnsignedSerializableClosure($closure);
    }

    /**
     * Sets the serializable closure secret key.
     *
     * @param string|null $secret
     * @return void
     */
    public static function setSecretKey($secret)
    {
        Signed::$signer = $secret
            ? new Hmac($secret)
            : null;
    }

    /**
     * Sets the serializable closure secret key.
     *
     * @param Closure|null $transformer
     * @return void
     */
    public static function transformUseVariablesUsing($transformer)
    {
        Native::$transformUseVariables = $transformer;
    }

    /**
     * Sets the serializable closure secret key.
     *
     * @param Closure|null $resolver
     * @return void
     */
    public static function resolveUseVariablesUsing($resolver)
    {
        Native::$resolveUseVariables = $resolver;
    }

    /**
     * Resolve the closure with the given arguments.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array($this->serializable, func_get_args());
    }

    /**
     * Gets the closure.
     *
     * @return Closure
     */
    public function getClosure()
    {
        return $this->serializable->getClosure();
    }

    /**
     * Get the serializable representation of the closure.
     *
     * @return array
     */
    public function __serialize()
    {
        return [
            'serializable' => $this->serializable,
        ];
    }

    /**
     * Restore the closure after serialization.
     *
     * @param array $data
     * @return void
     *
     * @throws InvalidSignatureException
     */
    public function __unserialize($data)
    {
        if (Signed::$signer && !$data['serializable'] instanceof Signed) {
            throw new InvalidSignatureException();
        }

        $this->serializable = $data['serializable'];
    }
}
