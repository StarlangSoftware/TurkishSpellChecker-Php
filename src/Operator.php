<?php

namespace olcaytaner\SpellChecker;

enum Operator
{
    /** No change is made to the Word. */
case NO_CHANGE;

    /** The Word is changed into a Word in the misspellings list */
case MISSPELLED_REPLACE;

    /** The Word is changed into a Candidate by deleting, adding, replacing a character or swapping two consecutive characters. */
case SPELL_CHECK;

    /** The Word is split into multiple Candidates. */
case SPLIT;

    /** The Word and the Word after are merged into one Candidate. */
case FORWARD_MERGE;

    /** The Word and the Word before are merged into one Candidate. */
case BACKWARD_MERGE;

    /** The Word is changed into a Candidate based on the context based spell checking algorithm. */
case CONTEXT_BASED;

    /** The Word is changed into a Candidate based on the trie based spell checking algorithm. */
case TRIE_BASED;
}