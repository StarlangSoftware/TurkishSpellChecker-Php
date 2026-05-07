<?php

namespace olcaytaner\SpellChecker;

class TrieCandidate extends Candidate
{
    private int $currentIndex;
    private float $currentPenalty;

    /**
     * Constructs a TrieCandidate object.
     *
     * @param string $word the candidate word
     * @param int $currentIndex the current index of the candidate word
     * @param float $currentPenalty the currentPenalty associated with the candidate word
     */
    public function __construct(string $word, int $currentIndex, float $currentPenalty)
    {
        parent::__construct($word, Operator::TRIE_BASED);
        $this->currentIndex = $currentIndex;
        $this->currentPenalty = $currentPenalty;
    }

    /**
     * Returns the current index of the candidate word.
     *
     * @return int the current index of the candidate word
     */
    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }

    /**
     * Returns the currentPenalty value associated with the candidate word.
     *
     * @return float the currentPenalty value associated with the candidate word
     */
    public function getCurrentPenalty(): float
    {
        return $this->currentPenalty;
    }

    /**
     * Increments the current index of the candidate word by 1.
     */
    public function nextIndex(): void
    {
        $this->currentIndex++;
    }
}