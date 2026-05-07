<?php

namespace olcaytaner\SpellChecker;

class SpellCheckerParameter
{
    private float $threshold = 0.0;
    private bool $suffixCheck = true;
    private bool $rootNGram = true;
    private int $minWordLength = 4;
    private ?string $domain = null;

    /**
     * Constructs a SpellCheckerParameter object with default values.
     * The default threshold is 0.0, the suffix check is enabled, the root ngram is enabled,
     * the minimum word length is 4 and domain name value is null.
     */
    public function __construct(){

    }

    /**
     * Sets the threshold value used in calculating the n-gram probabilities.
     *
     * @param float $threshold the threshold for the spell checker
     */
    public function setThreshold(float $threshold): void{
        $this->threshold = $threshold;
    }

    /**
     * Enables or disables suffix check for the spell checker.
     *
     * @param bool $suffixCheck a boolean indicating whether the suffix check should be enabled (true) or disabled (false)
     */
    public function setSuffixCheck(bool $suffixCheck): void{
        $this->suffixCheck = $suffixCheck;
    }

    /**
     * Enables or disables the root n-gram for the spell checker.
     *
     * @param bool $rootNGram a boolean indicating whether the root n-gram should be enabled (true) or disabled (false)
     */
    public function setRootNGram(bool $rootNGram): void{
        $this->rootNGram = $rootNGram;
    }

    /**
     * Sets the minimum length of words viable for spell checking.
     *
     * @param int $minWordLength the minimum word length for the spell checker
     */
    public function setMinWordLength(int $minWordLength): void{
        $this->minWordLength = $minWordLength;
    }

    /**
     * Sets the domain name to the specified value.
     *
     * @param string $domain the new domain name to set for this object
     */
    public function setDomain(string $domain): void{
        $this->domain = $domain;
    }

    /**
     * Returns the threshold value used in calculating the n-gram probabilities.
     *
     * @return float the threshold for the spell checker
     */
    public function getThreshold(): float{
        return $this->threshold;
    }

    /**
     * Returns whether suffix check is enabled for the spell checker.
     *
     * @return bool a boolean indicating whether suffix check is enabled for the spell checker
     */
    public function suffixCheck(): bool{
        return $this->suffixCheck;
    }

    /**
     * Returns whether the root n-gram is enabled for the spell checker.
     *
     * @return bool a boolean indicating whether the root n-gram is enabled for the spell checker
     */
    public function isRootNGram(): bool{
        return $this->rootNGram;
    }

    /**
     * Returns the minimum length of words viable for spell checking.
     *
     * @return int the minimum word length for the spell checker
     */
    public function getMinWordLength(): int{
        return $this->minWordLength;
    }

    /**
     * Returns the domain name
     *
     * @return string|null the domain name
     */
    public function getDomain(): ?string{
        return $this->domain;
    }
}