<?php

namespace MattFerris\Di;

interface BundleInterface
{
    /**
     * @param ContainerInterface $di
     */
    public function register(ContainerInterface $di);
}

