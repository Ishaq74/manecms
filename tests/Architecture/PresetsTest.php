<?php

arch('php')->preset()->php();

arch('security')->preset()->security();

// Bounded contexts keep their own models and policies in App\Domain (ADR 0001); see DomainTest.
arch('laravel')->preset()->laravel()->ignoring('App\Domain');
