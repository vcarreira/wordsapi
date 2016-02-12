WordsApi
=========

A wrapper around [WordsAPI](https://www.wordsapi.com).

## Installation

Add the following line to the `require` section of `composer.json`:

```json
{
    "require": {
        "vcarreira/wordsapi": ">= 1.0"
    }
}
```

## Usage
In order to use the wrapper you must first get an API key from [WordsAPI](https://www.wordsapi.com). The following examples show how to use the WordsService to fetch information regarding some word.

```php
    $service = new \WordsApi\WordService(API_KEY);
    $word = $service->word('effect');
    var_dump($word->definitions());
    var_dump($word->synonyms());
    var_dump($word->rhymes());
    var_dump($word->pronunciation());
```

## Limitation

Version 1.0 does not support search requests.

## Links
* [WordsAPI](https://www.wordsapi.com)
* [WordsAPI documentation](https://www.wordsapi.com/docs)
