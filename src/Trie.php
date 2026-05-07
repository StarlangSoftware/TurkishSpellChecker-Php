<?php

namespace olcaytaner\SpellChecker;

use Transliterator;

class Trie
{
    private TrieNode $rootNode;

    /**
     * A constructor of {@link Trie} class which constructs a new Trie with an empty root node
     */
    public function __construct()
    {
        $this->rootNode = new TrieNode();
    }

    /**
     * Inserts a new word into the Trie
     *
     * @param string $word The word to be inserted
     */
    public function insert(string $word): void
    {
        $currentNode = $this->rootNode;
        for ($i = 0; $i < mb_strlen($word); $i++) {
            $ch = mb_substr($word, $i, 1);
            if ($currentNode->getChild($ch) === null) {
                $currentNode->addChild($ch, new TrieNode());
            }
            $currentNode = $currentNode->getChild($ch);
        }
        $currentNode->setIsWord(true);
    }

    /**
     * Checks if a word is in the Trie
     *
     * @param string $word The word to be searched for
     * @return true if the word is in the Trie, false otherwise
     */
    public function search(string $word): bool{
        $currentNode = $this->getTrieNode((Transliterator::create("tr-Lower")->transliterate($word)));
        return $currentNode !== null && $currentNode->isWord();
    }

    /**
     * Checks if a given prefix exists in the Trie
     *
     * @param string $prefix The prefix to be searched for
     * @return bool true if the prefix exists, false otherwise
     */
    public function startsWith(string $prefix): bool{
        return $this->getTrieNode((Transliterator::create("tr-Lower")->transliterate($prefix))) !== null;
    }

    /**
     * Returns the TrieNode corresponding to the last character of a given word
     *
     * @param string $word The word to be searched for
     * @return TrieNode|null The TrieNode corresponding to the last character of the word
     */
    public function getTrieNode(string $word): ?TrieNode{
        $currentNode = $this->rootNode;
        for ($i = 0; $i < mb_strlen($word); $i++) {
            $ch = mb_substr($word, $i, 1);
            if ($currentNode->getChild($ch) === null) {
                return null;
            }
            $currentNode = $currentNode->getChild($ch);
        }
        return $currentNode;
    }
}