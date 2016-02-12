<?php

namespace WordsApi;

/**
 * Class to retrieve all sorts of information regarding an English word.
 * Wrapper for the information available at {@link https://www.wordsapi.com Words API}.
 *
 * @author     Vitor Carreira
 *
 * @link       https://github.com/vcarreira
 */
class Word
{
    /**
     * The word represented by this instance.
     *
     * @var string
     */
    private $word = null;

    /**
     * The service used to make requests.
     *
     * @var \WordsApi\WordService
     */
    private $service = null;

    /**
     * The internal cache containing the results.
     *
     * @var array
     */
    private $cache = [];

    /**
     * A flag stating if the word should pre-fetch all its details when instantiated.
     *
     * @var bool
     */
    private $prefetchDetails;

    /**
     * Creates a new word instance.
     *
     * @param string                $word            the word to query.
     * @param \WordsApi\WordService $service         the service used to make the requests.
     * @param bool                  $prefetchDetails true to pre-fetch the word details; defaults to false.
     */
    public function __construct($word, $service, $prefetchDetails = false)
    {
        $this->word = $word;
        $this->service = $service;
        $this->prefetchDetails = $prefetchDetails;
        if ($this->prefetchDetails) {
            $this->definitions();
        }
    }

    /**
     * Fetches the meaning of the word grouped by it's part of the speech.
     *
     * @param bool|null $fetchDetails true to fetch additional details associated to the definition.
     *                                Same effect as pre-fetching the details.
     *
     * @return array|false returns an associative array containing the meaning of
     *                     the word indexed by part of the speech (verb, noun, etc),
     *                     returns false if the word is not found.
     */
    public function definitions($fetchDetails = null)
    {
        return $this->make('definitions', $fetchDetails);
    }

    /**
     * Fetches a list of example sentences using this word.
     *
     * @return array|false returns an array with a list of sentences,
     *                     returns false if the word is not found.
     */
    public function sentences()
    {
        return $this->make('examples');
    }

    /**
     * Fetches the list of synonyms for this word.
     *
     * @return array|false returns an array with a list of synonyms,
     *                     returns false if the word is not found.
     */
    public function synonyms()
    {
        return $this->make('synonyms');
    }

    /**
     * Fetches the list of antonyms for this word.
     *
     * @return array|false returns an array with a list of antonyms,
     *                     returns false if the word is not found.
     */
    public function antonyms()
    {
        return $this->make('antonyms');
    }

    /**
     * Fetches the list of words that are more generic (hypernyms) than the original word.
     * E.g. car is a generic word for a hatchback.
     *
     * @return array|false returns an array with a list of hypernyms,
     *                     returns false if the word is not found.
     */
    public function genericWords()
    {
        return $this->make('typeOf');
    }

    /**
     * Fetches the list of words that are more specific (hyponyms) than the original word.
     * E.g. violet, lavender, mauve, etc are specific words for purple.
     *
     * @return array|false returns an array with a list of hyponyms,
     *                     returns false if the word is not found.
     */
    public function specificWords()
    {
        return $this->make('hasTypes');
    }

    /**
     * Fetches the larger whole to which this word belong (holonyms).
     * E.g. eye is part of a face.
     *
     * @return array|false returns an array with a list of holonyms,
     *                     returns false if the word is not found.
     */
    public function isPartOf()
    {
        return $this->make('partOf');
    }

    /**
     * Fetches the list of words that are part of this word (meronyms).
     * E.g. face has eyes, chin, eyebrown, etc.
     *
     * @return array|false returns an array with a list of meronyms,
     *                     returns false if the word is not found.
     */
    public function parts()
    {
        return $this->make('hasParts');
    }

    /**
     * Fetches the list of words that this word is an example of.
     * E.g. Thatcher is also known as a stateswoman.
     *
     * @return array|false returns an array list of words,
     *                     returns false if the word is not found.
     */
    public function knownAs()
    {
        return $this->make('instanceOf');
    }

    /**
     * Fetches the list of words that are concrete instances of this word.
     * E.g. isabella, marie antoinette are concrete instances for the word queen.
     *
     * @return array|false returns an array with a list of words,
     *                     returns false if the word is not found.
     */
    public function instances()
    {
        return $this->make('hasInstances');
    }

    /**
     * Fetches the list of words that are similar (but not synonyms) of this word.
     *
     * @return array|false returns an array with a list of words,
     *                     returns false if the word is not found.
     */
    public function similarWords()
    {
        return $this->make('similarTo');
    }

    /**
     * Fetches the list of substances which this word is part of.
     * E.g. water is a substance found in a teardrop or snowflake.
     *
     * @return array|false returns an array with a list of substances,
     *                     returns false if the word is not found.
     */
    public function substanceOf()
    {
        return $this->make('substanceOf');
    }

    /**
     * Fetches the list of substances that are part of this word.
     * E.g. tear is made of water or h2o.
     *
     * @return array|false returns an array with a list of substances,
     *                     returns false if the word is not found.
     */
    public function substances()
    {
        return $this->make('hasSubstances');
    }

    /**
     * Fetches the categories this word belongs to.
     * E.g. weather belongs to the category meteorology, navigation, etc.
     *
     * @return array|false returns an array with a list of categories,
     *                     returns false if the word is not found.
     */
    public function category()
    {
        return $this->make('inCategory');
    }

    /**
     * Fetches the list of subcategories for this word.
     * E.g. cyclone, front, anticyclone are subcategories of the word meteorology.
     *
     * @return array|false returns an array with a list of subcategories,
     *                     returns false if the word is not found.
     */
    public function subCategories()
    {
        return $this->make('hasCategories');
    }

    /**
     * Fetches the list of words where the word is typically used.
     * E.g. bastille is typically used in France.
     *
     * @return array|false returns an array with a list of words,
     *                     returns false if the word is not found.
     */
    public function region()
    {
        return $this->make('inRegion');
    }

    /**
     * Fetches the list of words used on some region. The word should refer to a region.
     * E.g. bastille, legionnaire, battle of ivry are words typically used in France.
     *
     * @return array|false returns an array with a list of words,
     *                     returns false if the word is not found.
     */
    public function regionOf()
    {
        return $this->make('regionOf');
    }

    /**
     * Fetches the list of syllables for this word.
     *
     * @return array|false returns an array with a list of syllables,
     *                     returns false if the word is not found.
     */
    public function syllables()
    {
        return $this->make('syllables');
    }

    /**
     * Fetches the list of IPA phonemes for this word.
     * Because the pronunciation of the word could differ according to the part of the speech,
     * the phonemes are indexed by part of the speech.
     *
     * @return array|false returns an associative array containing the phonemes of
     *                     the word indexed by part of the speech (verb, noun, etc),
     *                     returns a simple array containing the phonemes of
     *                     the word if it is pronounced the same regardless of the part of speech
     *                     it is used as,
     *                     returns false if the word is not found.
     */
    public function pronunciation()
    {
        return $this->make('pronunciation');
    }

    /**
     * Fetches the list of words this word rhymes with.
     * Because the pronunciation of the word could differ according to the part of the speech,
     * the rhymes are indexed by part of the speech.
     *
     * @return array|false returns an associative array containing the rhymes of
     *                     the word indexed by part of the speech (verb, noun, etc),
     *                     returns a simple array containing the rhymes of
     *                     the word if it is pronounced the same regardless of the part of speech
     *                     it is used as,
     *                     returns false if the word is not found.
     */
    public function rhymes()
    {
        return $this->make('rhymes');
    }

    /**
     * Fetches the frequency score for this word.
     *
     * @return array|false returns an array with the zipf score,
     *                     the perMillion usage and the diversity score for the word,
     *                     returns false if the word is not found.
     */
    public function frequency()
    {
        return $this->make('frequency');
    }

    /**
     * Checks if the result is cached. Otherwise
     * it invokes the service method to retrieve the results.
     * Results are cached on a word basis to optimize the number
     * of API calls.
     */
    private function make($verb, $fetchDetails = null)
    {
        if (isset($this->cache[$verb])) {
            return $this->cache[$verb];
        }

        $data = $this->service->fetch(
            $this->word,
            $verb,
            is_null($fetchDetails) ? $this->prefetchDetails : (bool) $fetchDetails
        );

        if ($data === false) {
            return false;
        }
        $this->cache = array_merge($this->cache, $data);

        return $this->cache[$verb];
    }
}
