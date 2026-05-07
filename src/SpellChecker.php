<?php

namespace olcaytaner\SpellChecker;

use olcaytaner\Corpus\Sentence;

abstract class SpellChecker
{
    /**
     * The spellCheck method which takes a {@link Sentence} as an input.
     *
     * @param Sentence $sentence {@link Sentence} type input.
     * @return Sentence Sentence result.
     */
    abstract public function spellCheck(Sentence $sentence): Sentence;
}