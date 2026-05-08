<?php

namespace olcaytaner\SpellChecker;

use olcaytaner\Corpus\Sentence;
use olcaytaner\Dictionary\Dictionary\Word;
use olcaytaner\MorphologicalAnalysis\MorphologicalAnalysis\FsmMorphologicalAnalyzer;
use olcaytaner\NGram\NGram;

class NGramSpellChecker extends SimpleSpellChecker
{
    private NGram $nGram;

    /**
     * Another constructor of {@link NGramSpellChecker} class which takes an {@link FsmMorphologicalAnalyzer}, an {@link NGram}
     * and a {@link SpellCheckerParameter} as inputs. Then, calls its super class {@link SimpleSpellChecker} with given {@link FsmMorphologicalAnalyzer}
     * and {@link SpellCheckerParameter}. Finally, it assigns given {@link NGram} to the nGram variable.
     *
     * @param FsmMorphologicalAnalyzer $fsm {@link FsmMorphologicalAnalyzer} type input.
     * @param NGram $nGram {@link NGram} type input.
     * @param SpellCheckerParameter|null $parameter {@link SpellCheckerParameter} type input.
     */
    public function __construct(FsmMorphologicalAnalyzer $fsm, NGram $nGram, ?SpellCheckerParameter $parameter)
    {
        parent::__construct($fsm, $parameter);
        $this->nGram = $nGram;
    }

    /**
     * Checks the morphological analysis of the given word in the given index. If there is no misspelling, it returns
     * the longest root word of the possible analysis.
     *
     * @param Sentence $sentence Sentence to be analyzed.
     * @param int $index Index of the word
     * @return Word|null If the word is misspelled, null; otherwise the longest root word of the possible analysis.
     */
    private function checkAnalysisAndSetRootForWordAtIndex(Sentence $sentence, int $index): ?Word
    {
        if ($index < $sentence->wordCount()) {
            $wordName = $sentence->getWord($index)->getName();
            if ((preg_match("/\d+/", $wordName) && preg_match("/[a-zA-ZçöğüşıÇÖĞÜŞİ]+/", $wordName) && !str_contains($wordName, "'")) || mb_strlen($wordName) < $this->parameter->getMinWordLength()) {
                return $sentence->getWord($index);
            }
            $fsmParses = $this->fsm->morphologicalAnalysis($wordName);
            if ($fsmParses->size() != 0) {
                if ($this->parameter->isRootNGram()) {
                    return $fsmParses->getParseWithLongestRootWord()->getWord();
                } else {
                    return $sentence->getWord($index);
                }
            } else {
                $upperCaseWordName = Word::toCapital($wordName);
                $upperCaseFsmParses = $this->fsm->morphologicalAnalysis($upperCaseWordName);
                if ($upperCaseFsmParses->size() != 0) {
                    if ($this->parameter->isRootNGram()) {
                        return $upperCaseFsmParses->getParseWithLongestRootWord()->getWord();
                    } else {
                        return $sentence->getWord($index);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Checks the morphological analysis of the given word. If there is no misspelling, it returns
     * the longest root word of the possible analysis.
     *
     * @param string $word Word to be analyzed.
     * @return Word|null If the word is misspelled, null; otherwise the longest root word of the possible analysis.
     */
    private function checkAnalysisAndSetRoot(string $word): ?Word
    {
        $fsmParsesOfWord = $this->fsm->morphologicalAnalysis($word);
        if ($fsmParsesOfWord->size() != 0) {
            if ($this->parameter->isRootNGram()) {
                return $fsmParsesOfWord->getParseWithLongestRootWord()->getWord();
            }
            return new Word($word);
        }
        $fsmParsesOfCapitalizedWord = $this->fsm->morphologicalAnalysis(Word::toCapital($word));
        if ($fsmParsesOfCapitalizedWord->size() != 0) {
            if ($this->parameter->isRootNGram()) {
                return $fsmParsesOfCapitalizedWord->getParseWithLongestRootWord()->getWord();
            }
            return new Word($word);
        }
        return null;
    }

    /**
     * Returns the bi-gram probability P(word2 | word1) for the given bigram consisting of two words.
     * @param string $word1 First word in bi-gram
     * @param string $word2 Second word in bi-gram
     * @return float Bi-gram probability P(word2 | word1)
     */
    private function getProbability(string $word1, string $word2): float
    {
        return $this->nGram->getProbability($word1, $word2);
    }

    public function spellCheck(Sentence $sentence): Sentence
    {
        $previousRoot = null;
        $result = new Sentence();
        $root = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, 0);
        $nextRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, 1);
        $i = 0;
        while ($i < $sentence->wordCount()) {
            $nextWord = null;
            $previousWord = null;
            $nextNextWord = null;
            $previousPreviousWord = null;
            $word = $sentence->getWord($i);
            if ($i > 0) {
                $previousWord = $sentence->getWord($i - 1);
            }
            if ($i > 1) {
                $previousPreviousWord = $sentence->getWord($i - 2);
            }
            if ($i < $sentence->wordCount() - 1) {
                $nextWord = $sentence->getWord($i + 1);
            }
            if ($i < $sentence->wordCount() - 2) {
                $nextNextWord = $sentence->getWord($i + 2);
            }
            if ($this->forcedMisspellCheck($word, $result)) {
                $previousRoot = $this->checkAnalysisAndSetRootForWordAtIndex($result, $result->wordCount() - 1);
                $root = $nextRoot;
                $nextRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $i + 2);
                $i++;
                continue;
            }
            if ($this->forcedBackwardMergeCheck($word, $result, $previousWord) || $this->forcedSuffixMergeCheck($word, $result, $previousWord)) {
                $previousRoot = $this->checkAnalysisAndSetRootForWordAtIndex($result, $result->wordCount() - 1);
                $root = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $i + 1);
                $nextRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $i + 2);
                $i++;
                continue;
            }
            if ($this->forcedForwardMergeCheck($word, $result, $nextWord) || $this->forcedHyphenMergeCheck($word, $result, $previousWord, $nextWord)) {
                $i++;
                $previousRoot = $this->checkAnalysisAndSetRootForWordAtIndex($result, $result->wordCount() - 1);
                $root = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $i + 1);
                $nextRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $i + 2);
                $i++;
                continue;
            }
            if ($this->forcedSplitCheck($word, $result) || $this->forcedShortcutSplitCheck($word, $result)) {
                $previousRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $result->wordCount() - 1);
                $root = $nextRoot;
                $nextRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $i + 2);
                $i++;
                continue;
            }
            if ($this->parameter->suffixCheck()) {
                if ($this->forcedDeDaSplitCheck($word, $result) || $this->forcedSuffixSplitCheck($word, $result) || $this->forcedQuestionSuffixSplitCheck($word, $result)) {
                    $previousRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $result->wordCount() - 1);
                    $root = $nextRoot;
                    $nextRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $i + 2);
                    $i++;
                    continue;
                }
            }
            if ($root === null || (mb_strlen($word->getName()) < $this->parameter->getMinWordLength() && $this->fsm->morphologicalAnalysis($word->getName())->size() == 0)) {
                $candidates = [];
                if ($root === null) {
                    $candidates = $this->candidateList($word, $sentence);
                    $candidates = array_merge($candidates, $this->splitCandidatesList($word));
                }
                $candidates = array_merge($candidates, $this->mergedCandidatesList($previousWord, $word, $nextWord));
                $bestCandidate = new Candidate($word->getName(), Operator::NO_CHANGE);
                $bestRoot = $word;
                $bestProbability = $this->parameter->getThreshold();
                foreach ($candidates as $candidate) {
                    if ($candidate->getOperator() == Operator::SPELL_CHECK || $candidate->getOperator() == Operator::MISSPELLED_REPLACE
                        || $candidate->getOperator() == Operator::CONTEXT_BASED || $candidate->getOperator() == Operator::TRIE_BASED) {
                        $root = $this->checkAnalysisAndSetRoot($candidate->getName());
                    }
                    if ($candidate->getOperator() == Operator::BACKWARD_MERGE && $previousWord != null) {
                        $root = $this->checkAnalysisAndSetRoot($previousWord->getName() . $word->getName());
                        if ($previousPreviousWord != null) {
                            $previousRoot = $this->checkAnalysisAndSetRoot($previousPreviousWord->getName());
                        }
                    }
                    if ($candidate->getOperator() == Operator::FORWARD_MERGE && $nextWord != null) {
                        $root = $this->checkAnalysisAndSetRoot($word->getName() . $nextWord->getName());
                        if ($nextNextWord != null) {
                            $nextRoot = $this->checkAnalysisAndSetRoot($nextNextWord->getName());
                        }
                    }
                    if ($previousRoot != null) {
                        if ($candidate->getOperator() == Operator::SPLIT) {
                            $root = $this->checkAnalysisAndSetRoot(explode(" ", $candidate->getName())[0]);
                        }
                        $previousProbability = $this->getProbability($previousRoot->getName(), $root->getName());
                    } else {
                        $previousProbability = 0.0;
                    }
                    if ($nextRoot != null) {
                        if ($candidate->getOperator() == Operator::SPLIT) {
                            $root = $this->checkAnalysisAndSetRoot(explode(" ", $candidate->getName())[1]);
                        }
                        $nextProbability = $this->getProbability($root->getName(), $nextRoot->getName());
                    } else {
                        $nextProbability = 0.0;
                    }
                    if (max($previousProbability, $nextProbability) > $bestProbability || count($candidates) == 1) {
                        $bestCandidate = $candidate;
                        $bestRoot = $root;
                        $bestProbability = max($previousProbability, $nextProbability);
                    }
                }
                if ($bestCandidate->getOperator() == Operator::FORWARD_MERGE) {
                    $i++;
                }
                if ($bestCandidate->getOperator() == Operator::BACKWARD_MERGE) {
                    $result->replaceWord($result->wordCount() - 1, new Word($bestCandidate->getName()));
                } else {
                    if ($bestCandidate->getOperator() == Operator::SPLIT) {
                        $this->addSplitWords($bestCandidate->getName(), $result);
                    } else {
                        $result->addWord(new Word($bestCandidate->getName()));
                    }
                }
                $root = $bestRoot;
            } else {
                $result->addWord($word);
            }
            $previousRoot = $root;
            $root = $nextRoot;
            $nextRoot = $this->checkAnalysisAndSetRootForWordAtIndex($sentence, $i + 2);
            $i++;
        }
        return $result;
    }
}