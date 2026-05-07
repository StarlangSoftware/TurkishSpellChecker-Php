<?php

namespace olcaytaner\SpellChecker;

class TrieNode
{
    private array $children;
    private bool $isWord;

    /**
     * A constructor of {@link TrieNode} class which constructs a new TrieNode with an empty children HashMap
     */
    public function __construct()
    {
        $this->children = [];
    }

    /**
     * Returns the child TrieNode with the given character as its value.
     * @param string $ch The character value of the child TrieNode.
     * @return null|TrieNode with the given character value.
     */
    public function getChild(string $ch): ?TrieNode
    {
        return $this->children[$ch] ?? null;
    }

    /**
     * Adds a child TrieNode to the current TrieNode instance.
     *
     * @param string $ch the character key of the child node to be added.
     * @param TrieNode $child the TrieNode object to be added as a child.
     */
    public function addChild(string $ch, TrieNode $child): void
    {
        $this->children[$ch] = $child;
    }

    /**
     * Returns a string representation of the keys of all child TrieNodes of the current TrieNode instance.
     *
     * @return string a string of characters representing the keys of all child TrieNodes.
     */
    public function childrenToString(): string
    {
        return implode("", array_keys($this->children));
    }

    /**
     * Returns whether the current TrieNode represents the end of a word.
     * @return bool true if the current TrieNode represents the end of a word, false otherwise.
     */
    public function isWord(): bool
    {
        return $this->isWord;
    }

    /**
     * Sets whether the current TrieNode represents the end of a word.
     * @param bool $isWord true if the current TrieNode represents the end of a word, false otherwise.
     */
    public function setIsWord(bool $isWord): void
    {
        $this->isWord = $isWord;
    }
}