<?php

namespace olcaytaner\SpellChecker;

use olcaytaner\Corpus\Sentence;
use olcaytaner\Dictionary\Dictionary\TxtWord;
use olcaytaner\Dictionary\Dictionary\Word;
use olcaytaner\Dictionary\Language\TurkishLanguage;
use olcaytaner\MorphologicalAnalysis\MorphologicalAnalysis\FsmMorphologicalAnalyzer;
use Transliterator;

class SimpleSpellChecker extends SpellChecker
{
    protected FsmMorphologicalAnalyzer $fsm;
    protected SpellCheckerParameter $parameter;
    private array $mergedWords = [];
    private array $splitWords = [];
    private array $shortcuts = [
        "cc",
        "cm2",
        "cm",
        "gb",
        "ghz",
        "gr",
        "gram",
        "hz",
        "inc",
        "inch",
        "inç",
        "kg",
        "kw",
        "kva",
        "litre",
        "lt",
        "m2",
        "m3",
        "mah",
        "mb",
        "metre",
        "mg",
        "mhz",
        "ml",
        "mm",
        "mp",
        "ms",
        "kb",
        "mb",
        "gb",
        "tb",
        "pb",
        "kbps",
        "mt",
        "mv",
        "tb",
        "tl",
        "va",
        "volt",
        "watt",
        "ah",
        "hp",
        "oz",
        "rpm",
        "dpi",
        "ppm",
        "ohm",
        "kwh",
        "kcal",
        "kbit",
        "mbit",
        "gbit",
        "bit",
        "byte",
        "mbps",
        "gbps",
        "cm3",
        "mm2",
        "mm3",
        "khz",
        "ft",
        "db",
        "sn"
    ];
    private array $conditionalShortcuts = ["g", "v", "m", "l", "w", "s"];
    private array $questionSuffixList = [
        "mi",
        "mı",
        "mu",
        "mü",
        "miyim",
        "misin",
        "miyiz",
        "midir",
        "miydi",
        "mıyım",
        "mısın",
        "mıyız",
        "mıdır",
        "mıydı",
        "muyum",
        "musun",
        "muyuz",
        "mudur",
        "muydu",
        "müyüm",
        "müsün",
        "müyüz",
        "müdür",
        "müydü",
        "miydim",
        "miydin",
        "miydik",
        "miymiş",
        "mıydım",
        "mıydın",
        "mıydık",
        "mıymış",
        "muydum",
        "muydun",
        "muyduk",
        "muymuş",
        "müydüm",
        "müydün",
        "müydük",
        "müymüş",
        "misiniz",
        "mısınız",
        "musunuz",
        "müsünüz",
        "miyimdir",
        "misindir",
        "miyizdir",
        "miydiniz",
        "miydiler",
        "miymişim",
        "miymişiz",
        "mıyımdır",
        "mısındır",
        "mıyızdır",
        "mıydınız",
        "mıydılar",
        "mıymışım",
        "mıymışız",
        "muyumdur",
        "musundur",
        "muyuzdur",
        "muydunuz",
        "muydular",
        "muymuşum",
        "muymuşuz",
        "müyümdür",
        "müsündür",
        "müyüzdür",
        "müydünüz",
        "müydüler",
        "müymüşüm",
        "müymüşüz",
        "miymişsin",
        "miymişler",
        "mıymışsın",
        "mıymışlar",
        "muymuşsun",
        "muymuşlar",
        "müymüşsün",
        "müymüşler",
        "misinizdir",
        "mısınızdır",
        "musunuzdur",
        "müsünüzdür"
    ];

    /**
     * Another constructor of {@link SimpleSpellChecker} class which takes an {@link FsmMorphologicalAnalyzer} and a
     * {@link SpellCheckerParameter} as inputs, assigns {@link FsmMorphologicalAnalyzer} to the fsm variable and
     * {@link SpellCheckerParameter} to the parameter variable. Then, it calls the loadDictionaries method.
     *
     * @param FsmMorphologicalAnalyzer $fsm {@link FsmMorphologicalAnalyzer} type input.
     * @param SpellCheckerParameter|null $parameter {@link SpellCheckerParameter} type input.
     */
    public function __construct(FsmMorphologicalAnalyzer $fsm, ?SpellCheckerParameter $parameter = null)
    {
        $this->fsm = $fsm;
        if ($parameter === null) {
            $this->parameter = new SpellCheckerParameter();
        } else {
            $this->parameter = $parameter;
        }
        $this->loadDictionaries();
    }

    /**
     * Loads the merged and split lists from the specified files.
     */
    protected function loadDictionaries(): void
    {
        $fh = fopen("merged.txt", 'r');
        while ($line = fgets($fh)) {
            $list = explode(" ", trim($line));
            if (count($list) == 3) {
                $this->mergedWords[$list[0] . " " . $list[1]] = $list[2];
            }
        }
        fclose($fh);
        $fh = fopen("split.txt", 'r');
        while ($line = fgets($fh)) {
            $word = mb_substr($line, 0, strpos($line, ' '));
            $result = mb_substr($line, strpos($line, ' ') + 1);
            $this->splitWords[$word] = $result;
        }
        fclose($fh);
    }

    /**
     * The generateCandidateList method takes a String as an input. Firstly, it creates a String consists of lowercase Turkish letters
     * and an {@link ArrayList} candidates. Then, it loops i times where i ranges from 0 to the length of given word. It gets substring
     * from 0 to ith index and concatenates it with substring from i+1 to the last index as a new String called deleted. Then, adds
     * this String to the candidates {@link ArrayList}. Secondly, it loops j times where j ranges from 0 to length of
     * lowercase letters String and adds the jth character of this String between substring of given word from 0 to ith index
     * and the substring from i+1 to the last index, then adds it to the candidates {@link ArrayList}. Thirdly, it loops j
     * times where j ranges from 0 to length of lowercase letters String and adds the jth character of this String between
     * substring of given word from 0 to ith index and the substring from i to the last index, then adds it to the candidates {@link ArrayList}.
     *
     * @param string $word String input.
     * @return array list candidates.
     */
    private function generateCandidateList(string $word): array
    {
        $s = TurkishLanguage::$LOWERCASE_LETTERS;
        $candidates = [];
        for ($i = 0; $i < mb_strlen($word); $i++) {
            if ($i < mb_strlen($word) - 1) {
                $swapped = new Candidate(
                    mb_substr($word, 0, $i) . mb_substr($word, $i + 1, 1) . mb_substr($word, $i, 1) . mb_substr(
                        $word,
                        $i + 2
                    ), Operator::SPELL_CHECK
                );
                $candidates[] = $swapped;
            }
            if (str_contains(
                TurkishLanguage::$LETTERS,
                mb_substr($word, $i, 1) || str_contains("qwx", mb_substr($word, $i, 1))
            )) {
                $deleted = new Candidate(mb_substr($word, 0, $i) . mb_substr($word, $i + 1), Operator::SPELL_CHECK);
                if (preg_match("/\d+/", $deleted->getName())) {
                    $candidates[] = $deleted;
                }
                for ($j = 0; $j < mb_strlen($s); $j++) {
                    $replaced = new Candidate(
                        mb_substr($word, 0, $i) . mb_substr($s, $j, 1) . mb_substr($word, $i + 1),
                        Operator::SPELL_CHECK
                    );
                    $candidates[] = $replaced;
                }
                for ($j = 0; $j < mb_strlen($s); $j++) {
                    $added = new Candidate(
                        mb_substr($word, 0, $i) . mb_substr($s, $j, 1) . mb_substr($word, $i),
                        Operator::SPELL_CHECK
                    );
                    $candidates[] = $added;
                    if ($i == mb_strlen($word) - 1) {
                        $candidates[] = new Candidate($word . mb_substr($s, $j, 1), Operator::SPELL_CHECK);
                    }
                }
            }
        }
        return $candidates;
    }

    /**
     * The candidateList method takes a {@link Word} as an input and creates a candidates {@link ArrayList} by calling generateCandidateList
     * method with given word. Then, it loops i times, where i ranges from 0 to size of candidates {@link ArrayList} and creates a
     * {@link FsmParseList} by calling morphologicalAnalysis with each item of candidates {@link ArrayList}. If the size of
     * {@link FsmParseList} is 0, it then removes the ith item.
     *
     * @param Word $word {@link Word} input.
     * @return array candidates {@link ArrayList}.
     */
    protected function candidateList(Word $word, Sentence $sentence): array
    {
        $candidates = $this->generateCandidateList($word->getName());
        $i = 0;
        while ($i < count($candidates)) {
            $fsmParseList = $this->fsm->morphologicalAnalysis($candidates[$i]->getName());
            if ($fsmParseList->size() == 0) {
                $newCandidate = $this->fsm->getDictionary()->getCorrectForm($candidates[$i]->getName());
                if ($newCandidate !== null && $this->fsm->morphologicalAnalysis($newCandidate)->size() > 0) {
                    $candidates[$i] = new Candidate($newCandidate, Operator::MISSPELLED_REPLACE);
                } else {
                    array_splice($candidates, $i, 1);
                    $i--;
                }
            }
            $i++;
        }
        return $candidates;
    }

    /**
     * Checks if the given word is a misspelled word according to the misspellings list,
     * and if it is, then replaces it with its correct form in the given sentence.
     *
     * @param Word $word the {@link Word} to check for misspelling
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @return bool true if the word was corrected, false otherwise
     */
    protected function forcedMisspellCheck(Word $word, Sentence $result): bool
    {
        $forcedReplacement = $this->fsm->getDictionary()->getCorrectForm($word->getName());
        if ($forcedReplacement !== null) {
            $result->addWord(new Word($forcedReplacement));
            return true;
        }
        return false;
    }

    /**
     * Checks if the given word and its preceding word need to be merged according to the merged list.
     * If the merge is needed, the word and its preceding word are replaced with their merged form in the given sentence.
     *
     * @param Word $word the {@link Word} to check for merge
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @param Word|null $previousWord the preceding {@link Word} of the given {@link Word}
     * @return bool true if the word was merged, false otherwise
     */
    protected function forcedBackwardMergeCheck(Word $word, Sentence $result, ?Word $previousWord): bool
    {
        if ($previousWord !== null) {
            $forcedReplacement = $this->getCorrectForm(
                $result->getWord($result->wordCount() - 1)->getName() . " " . $word->getName(),
                $this->mergedWords
            );
            if ($forcedReplacement !== null) {
                $result->replaceWord($result->wordCount() - 1, new Word($forcedReplacement));
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the given word and its next word need to be merged according to the merged list.
     * If the merge is needed, the word and its next word are replaced with their merged form in the given sentence.
     *
     * @param Word $word the {@link Word} to check for merge
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @param Word|null $nextWord the next {@link Word} of the given {@link Word}
     * @return bool true if the word was merged, false otherwise
     */
    protected function forcedForwardMergeCheck(Word $word, Sentence $result, ?Word $nextWord): bool
    {
        if ($nextWord !== null) {
            $forcedReplacement = $this->getCorrectForm(
                $word->getName() . " " . $nextWord->getName(),
                $this->mergedWords
            );
            if ($forcedReplacement !== null) {
                $result->addWord(new Word($forcedReplacement));
                return true;
            }
        }
        return false;
    }

    /**
     * Given a multiword form, splits it and adds it to the given sentence.
     *
     * @param string $multiWord multiword form to split
     * @param Sentence $result the {@link Sentence} to add the split words to
     */
    protected function addSplitWords(string $multiWord, Sentence $result): void
    {
        $words = explode(" ", $multiWord);
        foreach ($words as $word) {
            $result->addWord(new Word($word));
        }
    }

    /**
     * Checks if the given word needs to be split according to the split list.
     * If the split is needed, the word is replaced with its split form in the given sentence.
     *
     * @param Word $word the {@link Word} to check for split
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @return bool true if the word was split, false otherwise
     */
    protected function forcedSplitCheck(Word $word, Sentence $result): bool
    {
        $forcedReplacement = $this->getCorrectForm($word->getName(), $this->splitWords);
        if ($forcedReplacement !== null) {
            $this->addSplitWords($forcedReplacement, $result);
            return true;
        }
        return false;
    }

    /**
     * Checks if the given word is a shortcut form, such as "5kg" or "2.5km".
     * If it is, it splits the word into its number and unit form and adds them to the given sentence.
     *
     * @param Word $word the {@link Word} to check for shortcut split
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @return bool true if the word was split, false otherwise
     */
    protected function forcedShortcutSplitCheck(Word $word, Sentence $result): bool
    {
        $shortcutRegex = "/(([1-9][0-9]*)|[0])(([.]|[,])[0-9]*)?(" . $this->shortcuts[0];
        for ($i = 1; $i < count($this->shortcuts); $i++) {
            $shortcutRegex .= "|" . $this->shortcuts[$i];
        }
        $shortcutRegex .= ")/";
        $conditionalShortcutRegex = "/(([1-9][0-9]{0,2})|[0])(([.]|[,])[0-9]*)?(" . $this->conditionalShortcuts[0];
        for ($i = 1; $i < count($this->conditionalShortcuts); $i++) {
            $conditionalShortcutRegex .= "|" . $this->conditionalShortcuts[$i];
        }
        $conditionalShortcutRegex .= ")/";
        if (preg_match($shortcutRegex, $word->getName()) || preg_match($conditionalShortcutRegex, $word->getName())) {
            $pair = $this->getSplitPair($word);
            $result->addWord(new Word($pair[0]));
            $result->addWord(new Word($pair[1]));
            return true;
        }
        return false;
    }

    /**
     * Checks if the given word has a "da" or "de" suffix that needs to be split according to a predefined set of rules.
     * If the split is needed, the word is replaced with its bare form and "da" or "de" in the given sentence.
     *
     * @param Word $word the {@link Word} to check for "da" or "de" split
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @return bool true if the word was split, false otherwise
     */
    protected function forcedDeDaSplitCheck(Word $word, Sentence $result): bool
    {
        $wordName = $word->getName();
        $capitalizedWordName = Word::toCapital($wordName);
        $txtWord = null;
        if (str_ends_with($wordName, "da") || str_ends_with($wordName, "de")) {
            if ($this->fsm->morphologicalAnalysis($wordName)->size() == 0 && $this->fsm->morphologicalAnalysis(
                    $capitalizedWordName
                )->size() == 0) {
                $newWordName = mb_substr($wordName, 0, mb_strlen($wordName) - 2);
                $fsmParseList = $this->fsm->morphologicalAnalysis($newWordName);
                $txtNewWord = $this->fsm->getDictionary()->getWordWithName(
                    Transliterator::create("tr-Lower")->transliterate($newWordName)
                );
                if ($txtNewWord instanceof TxtWord && $txtNewWord->isProperNoun()) {
                    $newWordNameCapitalized = Word::toCapital($newWordName);
                    if ($this->fsm->morphologicalAnalysis($newWordNameCapitalized . "'" . "da")->size() > 0) {
                        $result->addWord(new Word($newWordNameCapitalized . "'" . "da"));
                    } else {
                        $result->addWord(new Word($newWordNameCapitalized . "'" . "de"));
                    }
                    return true;
                }
                if ($fsmParseList->size() > 0) {
                    $txtWord = $this->fsm->getDictionary()->getWordWithName(
                        $fsmParseList->getParseWithLongestRootWord()->getWord()->getName()
                    );
                }
                if ($txtWord instanceof TxtWord && !$txtWord->isCode()) {
                    $result->addWord(new Word($newWordName));
                    if (TurkishLanguage::isBackVowel(Word::lastVowel($newWordName))) {
                        if ($txtWord->notObeysVowelHarmonyDuringAgglutination()) {
                            $result->addWord(new Word("de"));
                        } else {
                            $result->addWord(new Word("da"));
                        }
                    } else {
                        if ($txtWord->notObeysVowelHarmonyDuringAgglutination()) {
                            $result->addWord(new Word("da"));
                        } else {
                            $result->addWord(new Word("de"));
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks whether the given {@link Word} can be split into a proper noun and a suffix, with an apostrophe in between
     * and adds the split result to the {@link Sentence} if it's valid.
     *
     * @param Word $word the {@link Word} to check for forced suffix split.
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @return bool true if the split is successful, false otherwise.
     */
    protected function forcedSuffixSplitCheck(Word $word, Sentence $result): bool
    {
        $wordName = $word->getName();
        if ($this->fsm->morphologicalAnalysis($wordName)->size() > 0) {
            return false;
        }
        for ($i = 1; $i < mb_strlen($wordName); $i++) {
            $probableProperNoun = Word::toCapital(mb_substr($wordName, 0, $i));
            $probableSuffix = mb_substr($wordName, $i);
            $apostropheWord = $probableProperNoun . "'" . $probableSuffix;
            $txtWord = $this->fsm->getDictionary()->getWordWithName(
                Transliterator::create("tr-Lower")->transliterate($probableProperNoun)
            );
            if ($txtWord instanceof TxtWord && $txtWord->isProperNoun() && $this->fsm->morphologicalAnalysis(
                    $apostropheWord
                )->size() > 0) {
                $result->addWord(new Word($apostropheWord));
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the given word is a suffix like 'li' or 'lik' that needs to be merged with its preceding word which is a number.
     * If the merge is needed, the word and its preceding word are replaced with their merged form in the given sentence.
     *
     * @param Word $word the {@link Word} to check for merge
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @param Word $previousWord the preceding {@link Word} of the given {@link Word}
     * @return bool true if the word was merged, false otherwise
     */
    protected function forcedSuffixMergeCheck(Word $word, Sentence $result, Word $previousWord): bool
    {
        $liList = ["li", "lı", "lu", "lü"];
        $likList = ["lik", "lık", "luk", "lük"];
        if (in_array($word->getName(), $liList) || in_array($word->getName(), $likList)) {
            if (preg_match("/[0-9]+/", $previousWord->getName())) {
                foreach ($liList as $suffix) {
                    if (mb_strlen($word->getName()) == 2 && $this->fsm->morphologicalAnalysis(
                            $previousWord->getName() . "'" . $suffix
                        )->size() > 0) {
                        $result->replaceWord(
                            $result->wordCount() - 1,
                            new Word($previousWord->getName() . "'" . $suffix)
                        );
                        return true;
                    }
                }
                foreach ($likList as $suffix) {
                    if (mb_strlen($word->getName()) == 3 && $this->fsm->morphologicalAnalysis(
                            $previousWord->getName() . "'" . $suffix
                        )->size() > 0) {
                        $result->replaceWord(
                            $result->wordCount() - 1,
                            new Word($previousWord->getName() . "'" . $suffix)
                        );
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Checks whether the next word and the previous word can be merged if the current word is a hyphen,
     * an en-dash or an em-dash.
     * If the previous word and the next word exist and they are valid words,
     * it merges the previous word and the next word into a single word and add the new word to the sentence
     * If the merge is valid, it returns true.
     *
     * @param Word $word current {@link Word}
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @param Word $previousWord the {@link Word} before current word
     * @param Word $nextWord the {@link Word} after current word
     * @return bool true if merge is valid, false otherwise
     */
    protected function forcedHyphenMergeCheck(Word $word, Sentence $result, Word $previousWord, Word $nextWord): bool
    {
        if ($word->getName() == "-" || $word->getName() == "–" || $word->getName() == "—") {
            if (preg_match("/[a-zA-ZçöğüşıÇÖĞÜŞİ]+/", $previousWord->getName()) && preg_match(
                    "/[a-zA-ZçöğüşıÇÖĞÜŞİ]+/",
                    $nextWord->getName()
                )) {
                $newWordName = $previousWord->getName() . "-" . $nextWord->getName();
                if ($this->fsm->morphologicalAnalysis($newWordName)->size() > 0) {
                    $result->replaceWord($result->wordCount() - 1, new Word($newWordName));
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks whether the current word ends with a valid question suffix and split it if it does.
     * It splits the word with the question suffix and adds the two new words to the sentence.
     * If the split is valid, it returns true.
     *
     * @param Word $word current {@link Word}
     * @param Sentence $result the {@link Sentence} that the word belongs to
     * @return bool true if split is valid, false otherwise
     */
    protected function forcedQuestionSuffixSplitCheck(Word $word, Sentence $result): bool
    {
        $wordName = $word->getName();
        if ($this->fsm->morphologicalAnalysis($wordName)->size() > 0) {
            return false;
        }
        foreach ($this->questionSuffixList as $questionSuffix) {
            if (str_ends_with($wordName, $questionSuffix)) {
                $splitWordName = mb_substr($wordName, 0, mb_strrpos($wordName, $questionSuffix));
                if ($this->fsm->morphologicalAnalysis($splitWordName)->size() == 0) {
                    return false;
                }
                $splitWordRoot = $this->fsm->getDictionary()->getWordWithName($this->fsm->morphologicalAnalysis($splitWordName)->getParseWithLongestRootWord()->getWord()->getName());
                if ($this->fsm->morphologicalAnalysis($splitWordName)->size() > 0 && $splitWordRoot instanceof TxtWord && !$splitWordRoot->isCode()) {
                    $result->addWord(new Word($splitWordName));
                    $result->addWord(new Word($questionSuffix));
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Generates a list of merged candidates for the word and previous and next words.
     *
     * @param Word|null $previousWord The previous {@link Word} in the sentence.
     * @param Word $word The {@link Word} currently being checked.
     * @param Word|null $nextWord The next {@link Word} in the sentence.
     * @return array A list of merged candidates.
     */
    protected function mergedCandidatesList(?Word $previousWord, Word $word, ?Word $nextWord): array
    {
        $mergedCandidates = [];
        $backwardMergeCandidate = null;
        if ($previousWord !== null) {
            $backwardMergeCandidate = new Candidate($previousWord->getName() . $word->getName(), Operator::BACKWARD_MERGE);
            $fsmParseList = $this->fsm->morphologicalAnalysis($backwardMergeCandidate->getName());
            if ($fsmParseList->size() != 0) {
                $mergedCandidates[] = $backwardMergeCandidate;
            }
        }
        if ($nextWord !== null) {
            $forwardMergeCandidate = new Candidate($word->getName() . $nextWord->getName(), Operator::FORWARD_MERGE);
            if ($backwardMergeCandidate === null || $backwardMergeCandidate->getName() != $forwardMergeCandidate->getName()) {
                $fsmParseList = $this->fsm->morphologicalAnalysis($forwardMergeCandidate->getName());
                if ($fsmParseList->size() != 0) {
                    $mergedCandidates[] = $forwardMergeCandidate;
                }
            }
        }
        return $mergedCandidates;
    }

    /**
     * Generates a list of split candidates for the given word.
     *
     * @param Word $word The {@link Word} currently being checked.
     * @return array A list of split candidates.
     */
    protected function splitCandidatesList(Word $word): array
    {
        $splitCandidates = [];
        for ($i = 4; $i < mb_strlen($word->getName()) - 3; $i++) {
            $firstPart = mb_substr($word->getName(), 0, $i);
            $secondPart = mb_substr($word->getName(), $i);
            $fsmParseListFirst = $this->fsm->morphologicalAnalysis($firstPart);
            $fsmParseListSecond = $this->fsm->morphologicalAnalysis($secondPart);
            if ($fsmParseListFirst->size() != 0 && $fsmParseListSecond->size() != 0) {
                $splitCandidates[] = new Candidate($firstPart . " " . $secondPart, Operator::SPLIT);
            }
        }
        return $splitCandidates;
    }

    /**
     * Returns the correct form of a given word by looking it up in the provided dictionary.
     *
     * @param string $wordName the name of the word to look up in the dictionary
     * @param array $dictionary the dictionary to use for looking up the word
     * @return string|null the correct form of the word, as stored in the dictionary, or null if the word is not found
     */
    protected function getCorrectForm(string $wordName, array $dictionary): ?string
    {
        if (array_key_exists($wordName, $dictionary)) {
            return $dictionary[$wordName];
        }
        return null;
    }

    /**
     * Splits a word into two parts, a key and a value, based on the first non-numeric/non-punctuation character.
     *
     * @param Word $word the {@link Word} object to split
     * @return array an {@link AbstractMap.SimpleEntry} object containing the key (numeric/punctuation characters) and the value (remaining characters)
     */
    private function getSplitPair(Word $word): array
    {
        $key = " ";
        $j = 0;
        while ($j < mb_strlen($word->getName())) {
            if (preg_match("/[0-9]/", mb_substr($word->getName(), $j, 0)) || mb_substr($word->getName(), $j, 0) == "." || mb_substr($word->getName(), $j, 0) == ",") {
                $key .= mb_substr($word->getName(), $j, 0);
            } else {
                break;
            }
            $j++;
        }
        $value = mb_substr($word->getName(), $j);
        return [$key, $value];
    }

    /**
     * The spellCheck method takes a {@link Sentence} as an input and loops i times where i ranges from 0 to size of words in given sentence.
     * Then, it calls morphologicalAnalysis method with each word and assigns it to the {@link FsmParseList}, if the size of
     * {@link FsmParseList} is equal to the 0, it adds current word to the candidateList and assigns it to the candidates {@link ArrayList}.
     * if the size of candidates greater than 0, it generates a random number and selects an item from candidates {@link ArrayList} with
     * this random number and assign it as newWord. If the size of candidates is not greater than 0, it directly assigns the
     * current word as newWord. At the end, it adds the newWord to the result {@link Sentence}.
     *
     * @param Sentence $sentence {@link Sentence} type input.
     * @return Sentence Sentence result.
     */
    public function spellCheck(Sentence $sentence): Sentence
    {
        $result = new Sentence();
        $i = 0;
        while ($i < $sentence->wordCount()) {
            $word = $sentence->getWord($i);
            $nextWord = null;
            $previousWord = null;
            if ($i > 0) {
                $previousWord = $sentence->getWord($i - 1);
            }
            if ($i < $sentence->wordCount() - 1) {
                $nextWord = $sentence->getWord($i + 1);
            }
            if ($this->forcedMisspellCheck($word, $result) || $this->forcedBackwardMergeCheck($word, $result, $previousWord) || $this->forcedSuffixMergeCheck($word, $result, $previousWord)) {
                continue;
            }
            if ($this->forcedForwardMergeCheck($word, $result, $nextWord) || $this->forcedHyphenMergeCheck($word, $result, $previousWord, $nextWord)) {
                $i++;
                continue;
            }
            if ($this->forcedSplitCheck($word, $result) || $this->forcedShortcutSplitCheck($word, $result) || $this->forcedDeDaSplitCheck($word, $result) || $this->forcedQuestionSuffixSplitCheck($word, $result) || $this->forcedSuffixSplitCheck($word, $result)) {
                continue;
            }
            $fsmParseListFirst = $this->fsm->morphologicalAnalysis($word->getName());
            $upperCaseFsmParseList = $this->fsm->morphologicalAnalysis(Word::toCapital($word->getName()));
            if ($fsmParseListFirst->size() == 0 && $upperCaseFsmParseList->size() == 0) {
                $candidates = $this->mergedCandidatesList($previousWord, $word, $nextWord);
                if (count($candidates) == 0) {
                    $candidates = $this->candidateList($word, $sentence);
                }
                if (count($candidates) == 0) {
                    $candidates = $this->splitCandidatesList($word);
                }
                if (count($candidates) != 0) {
                    $randomCandidate = rand(0, count($candidates) - 1);
                    $newWord = new Word($candidates[$randomCandidate]->getName());
                    if ($candidates[$randomCandidate]->getOperator() == Operator::BACKWARD_MERGE) {
                        $result->replaceWord($result->wordCount() - 1, $newWord);
                        continue;
                    }
                    if ($candidates[$randomCandidate]->getOperator() == Operator::FORWARD_MERGE) {
                        $i++;
                    }
                    if ($candidates[$randomCandidate]->getOperator() == Operator::SPLIT) {
                        $this->addSplitWords($candidates[$randomCandidate]->getName(), $result);
                        continue;
                    }
                } else {
                    $newWord = $word;
                }
            } else {
                $newWord = $word;
            }
            $result->addWord($newWord);
            $i++;
        }
        return $result;
    }
}