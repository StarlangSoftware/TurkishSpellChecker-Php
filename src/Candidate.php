<?php

namespace olcaytaner\SpellChecker;

use olcaytaner\Dictionary\Dictionary\Word;

class Candidate extends Word
{
    private Operator $operator;

    /**
     * Constructs a new Candidate object with the specified candidate and operator.
     *
     * @param string $candidate The word candidate to be checked for spelling.
     * @param Operator $operator The operator to be applied to the candidate in the spell checking process.
     */
    public function __construct(string $candidate, Operator $operator){
        parent::__construct($candidate);
        $this->operator = $operator;
    }

    /**
     * Returns the operator associated with this candidate.
     *
     * @return Operator The operator associated with this candidate.
     */
    public function getOperator(): Operator {
        return $this->operator;
    }
}