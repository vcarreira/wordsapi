<?php

namespace WordsApi;

/**
 * Wrapper for the {@link https://www.wordsapi.com Words API}.
 *
 * @author     Vitor Carreira
 *
 * @link       https://github.com/vcarreira
 */
class WordService
{
    private $hiddenVerbs = [
        'syllables' => 'syllables',
        'pronunciation' => 'pronunciation',
    ];

    private $details = ['examples', 'synonyms', 'antonyms', 'typeOf', 'hasTypes', 'partOf', 'hasParts', 'instanceOf', 'hasInstances', 'similarTo', 'substanceOf', 'hasSubstances', 'inCategory', 'hasCategories', 'inRegion', 'regionOf'];

    private $flatTransformations = [
        'examples' => 'examples',
        'synonyms' => 'synonyms',
        'antonyms' => 'antonyms',
        'typeOf' => 'typeOf',
        'hasTypes' => 'hasTypes',
        'partOf' => 'partOf',
        'hasParts' => 'hasParts',
        'instanceOf' => 'instanceOf',
        'hasInstances' => 'hasInstances',
        'similarTo' => 'similarTo',
        'substanceOf' => 'substanceOf',
        'hasSubstances' => 'hasSubstances',
        'inCategory' => 'inCategory',
        'hasCategories' => 'hasCategories',
        'inRegion' => 'inRegion',
        'regionOf' => 'regionOf',
        'frequency' => 'frequency',
        'syllables' => 'syllables.list',
        'pronunciation' => 'pronunciation.all',
        'rhymes' => 'rhymes.all',
    ];

    private $groupTransformations = [
        'definitions' => ['definitions', 'partOfSpeech', 'definition'],
    ];

    private $timeout;
    private $api_key;

    /**
     * Instantiates a new wrapper.
     *
     * @param string $api_key the API key used to fetch the words.
     * @param int    $timeout the request timeout in seconds. Defaults to 5.
     */
    public function __construct($api_key, $timeout = 5)
    {
        $this->api_key = $api_key;
        $this->timeout = $timeout;
    }

    /**
     * Creates a new word instance.
     *
     * @param string $word            the word to query.
     * @param bool   $prefetchDetails true to pre-fetch word details; false otherwise.
     *
     * @return \WordsApi\Word a word instance used to fetch all types of information about a word.
     *
     * @see \WordsApi\Word
     */
    public function word($word, $prefetchDetails = false)
    {
        return new Word($word, $this, $prefetchDetails);
    }

    /**
     * Search for words.
     *
     * @param array $options associative array with the search options.
     * @param int   $page    the starting page. Defaults to 1.
     * @param int   $limit   the maximum number of results to return.
     *
     * @return array returns an array of words matching the search options.
     */
    public function search($options, $page = 1, $limit = 100)
    {
        throw new \BadMethodCallException('search method not implemented yet');
    }

    /**
     * Fetch information about a specific word.
     *
     * @param string $word            the word to fetch.
     * @param string $verb            the type of information to fetch. Check the {@link https://www.wordsapi.com/docs Words API documentation}.
     * @param bool   $prefetchDetails true to ignore the verb and pre-fetch all word details.
     *
     * @return array|false an associative array (indexed by verb) with the requested information,
     *                     false if the word is not found.
     */
    public function fetch($word, $verb, $prefetchDetails = false)
    {
        $url = 'https://wordsapiv1.p.mashape.com/words/'.rawurlencode($word);
        $verbIsHidden = array_key_exists($verb, $this->hiddenVerbs) || $prefetchDetails;
        if (!$verbIsHidden) {
            $url .= '/'.$verb;
        }
        $headers = [
            'X-Mashape-Key: '.$this->api_key,
            'Accept: application/json',
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($statusCode !== 200) {
            return false;
        }

        if (is_null($response) || empty($response)) {
            return false;
        }

        $data = json_decode($response, true);
        if ($verbIsHidden) {
            return $this->transformFullRequest($data);
        }

        return [$verb => $this->transformVerb($data, $verb)];
    }

    private function transformFullRequest($data)
    {
        $result = [];
        foreach ($this->hiddenVerbs as $verb) {
            $result[$verb] = $this->transformVerb($data, $verb);
        }
        foreach ($this->details as $verb) {
            $result[$verb] = [];
        }

        foreach ($data['results'] as $definition) {
            $partials = [];
            foreach ($this->details as $verb) {
                $partials[$verb] = $this->transformVerb($definition, $verb);
                $result[$verb] = array_merge($result[$verb], $partials[$verb]);
            }
            $result['definitions'][$definition['partOfSpeech']][] = [
                'definition' => $definition['definition'],
                'details' => $partials,
            ];
        }

        return $result;
    }

    private function transformVerb($data, $verb)
    {
        if (isset($this->flatTransformations[$verb])) {
            return $this->flatten($data, $this->flatTransformations[$verb]);
        }

        if (isset($this->groupTransformations[$verb])) {
            list($key, $groupByKey, $valueKey) = $this->groupTransformations[$verb];

            return $this->groupBy($data, $key, $groupByKey, $valueKey);
        }

        throw new \UnexpectedValueException("Unknown verb: $verb");
    }

    private function flatten($data, $keyToExtract)
    {
        $keys = explode('.', $keyToExtract);
        $returnPartial = count($keys) > 1;
        $array = $data;
        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                return $returnPartial ? $array : [];
            }
            $array = $array[$key];
        }

        return $array;
    }

    private function groupBy($data, $keyToExtract, $groupByKey, $valueKey)
    {
        $result = [];
        if (!isset($data[$keyToExtract])) {
            return $result;
        }
        foreach ($data[$keyToExtract] as $item) {
            $idx = $item[$groupByKey];
            $result[$idx][] = [$valueKey => $item[$valueKey]];
        }

        return $result;
    }
}
