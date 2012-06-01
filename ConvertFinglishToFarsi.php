<?php
@error_reporting(E_ALL);
@set_time_limit(0);

class ConvertFinglishToFarsi
{
	/**
	 * Default Options
	 */
	public $useFarsiNumbers = false; //Replace English numbers with Persian numbers
	public $leaveBBCodesIntact = false; //Ignore BB Codes

	/**
	 * Constants
	 */
	private $internetSubstrings;
	private $knownWordsTable = array();

	/**
	 * ConvertFinglishToFarsi::Constructor
	 *
	 * Description of function
	 *
     */
    public function __construct()
    {
		$this->internetSubstrings = array("www", "http", "ftp", ".edu", ".com", ".org",".net", ".tv", ".ws", "mailto:", ".us", ".ir", ".info", "؟", urldecode("%C2%A0")); 
		$this->knownWordsTable = array(
			" " => " ",
			"  " => "  ", // add space and double space to the table so it does not ask for it from the server
			"" => "",
			"." => ".",
			"\\n" => "\\n",
			"," => "،",
			"ii" => "یی",
			"nima" => "نیما",
			"yushij" => "یوشیج",
			"enshallah" => "انشا‌الله",
			"enshaallah" => "انشا‌الله",
			"canada" => "کانادا",
			"4" => "4",
			"+" => "+",
			"<" => "<",
			"&" => "&",
			"s" => "س",
			"x" => "خ",
			";" => "؛",
			"angeles" => "انجلس",
			"january" => "ژانویه",
			"february" => "فوریه",
			"march" => "مارث",
			"april" => "آوریل",
			"may" => "می",
			"june" => "ژوئن",
			"july" => "ژولای",
			"august" => "آگوست",
			"september" => "سپتامبر",
			"october" => "اکتبر",
			"november" => "نوامبر",
			"december" => "دسامبر",
			"\\\\" => "\\\\",
			"ahmadinejad" => "احمدی‌نژاد",
			"ahmadinezhad" => "احمدی‌نژاد",
			"ahmadinezhaad" => "احمدی‌نژاد",
			"sakht" => "سخت",
			"filter" => "فیلتر",
			"zan" => "زن",
			"maa" => "ما",
			"haa" => "ها",
			"enshalla" => "انشا‌الله",
			"chetoreh" => "چطوره",
			"khanoom" => "خانوم",
			"aazma" => "آزما",
			"dah" => "ده",
			"book" => "بوک",
			"tabestan" => "تابستان",
			"shekaste" => "شکسته",
			"kosh" => "کش",
			"sharab" => "شراب",
			"sharaab" => "شراب",
			"shebh" => "شبه",
			"joon" => "جون",
			"farsi" => "فارسی",
			"w" => "و",
			"khamenei" => "خامنه‌ای",
			"khaamenei" => "خامنه‌ای",
			"khaameneii" => "خامنه‌ای",
			"khameneii" => "خامنه‌ای",
			"gir" => "گیر",
			"koshte" => "کشته",
			"behnevis" => "بهنویس",
			"o" => "و",
			"ebi" => "ابی",
			"e" => urldecode('%D9%90') //define e as a separate Kasre (to be attached later with the word before)
		);
    }

	/**
	 * ConvertFinglishToFarsi::Convert
	 *
	 * Description of function
	 *
	 * @param $finglishText string Finglish text to be converted to farsi
	 * @return string Farsi text
     */
	public function Convert($finglishText)
	{
		$finglishText = str_replace("'", "`", $finglishText);
		$textWords = $this->separateLatinLetters($finglishText);

		$wordShouldBeConvertedToFarsi = array();
		
		$lowerCaseTextWord = array();
		
		for ($i=0; $i < count($textWords); $i++)
		{ 
			$lowerCaseTextWord = strtolower($textWords[$i]);
			$wordShouldBeConvertedToFarsi[$i] = $this->shouldBeConvertedToFarsi($lowerCaseTextWord);
		}
		
		$farsiText = $this->assembleFarsiTextUsingTable($textWords, $wordShouldBeConvertedToFarsi);
		return $farsiText;
	}

	/**
	 * ConvertFinglishToFarsi::separateLatinLetters
	 *
	 * Description of function
	 *
	 * @param $latinText string Blah blah blah
	 * @return string Blah blah blah
     */
	private function separateLatinLetters($latinText)
	{
		
		if ($this->leaveBBCodesIntact)
		{
			$latinTextParts = $this->separateTextBetweenSquareBrackets($latinText);

			$textWords = array();
			for ($i=0; $i < count($latinTextParts); $i++)
			{
				if (strlen($latinTextParts[$i]) > 0 && $latinTextParts[$i][0] == '[') // if its starts with a bracket, do not split on space
				{
					$this->useFarsiNumbers = false; // in case there exist a bracket, do not change numbers to farsi so the bulletin board message numbers remain intact
					array_push($latinTextParts[$i]);
				}
				else
				{
					$textWords = array_merge($textWords, $this->splitOnSpaces($latinTextParts[$i]) /* split on space but keep spaces */);
				}																			   																				   
			}

			//$latinText = str_replace('[', ' /[', $latinText); // replace [ with space+/[ to flag it with / and prevent conversion to farsi
		}
		else // if do not consider leaving bb codes intact (default)
		{
			$textWords = $this->splitOnSpaces($latinText); // split on space but keep spaces
		}
		
		$newTextWords = array();
		$counter = 0;

		for ($i = 0; $i < count($textWords); $i++)
		{
			if ($this->shouldBeConvertedToFarsi(strtolower($textWords[$i])))
			{
				$separatedWords = preg_split('/([^a-zA-Z`\'\/' . preg_quote('\\', '/') . '])/', $textWords[$i], -1, PREG_SPLIT_DELIM_CAPTURE);
				for ($j = 0; $j < count($separatedWords); $j++)
				{
					$newTextWords[$counter++] = strtolower($separatedWords[$j]);
				}
			}
			else
			{
				$newTextWords[$counter] = $textWords[$i];
				$counter++;
			}
		}
		return $newTextWords;
	}

	/**
	 * ConvertFinglishToFarsi::splitOnSpaces
	 *
	 * Split on space but keep spaces
	 *
	 * @param $text string Blah blah blah
	 * @return array Blah blah blah
     */
	private function splitOnSpaces($text)
	{
		$temp = explode(' ', $text);
		$result = array();

		for ($i = 0; $i < ((2 * count($temp)) - 1); $i++)
		{
			array_push($result, ($i % 2) ? ' ' : $temp[$i / 2]);
		}

		for ($i--; $i >= 0; $i--)
		{
			if (empty($result[$i])) unset($result[$i]);
		}

		return array_values($result);
	}

	/**
	 * ConvertFinglishToFarsi::shouldBeConvertedToFarsi
	 *
	 * Description of function
	 *
	 * @param $word string Blah blah blah
	 * @return bool Blah blah blah
     */
	private function shouldBeConvertedToFarsi($word)
	{
		
		if ($this->leaveBBCodesIntact && strlen($word) > 0 && $word[0] == '[') // when bbcodes are active, if it starst with '[', do not convert
		{
			return false;
		}

		if (array_search($word, $this->internetSubstrings) !== false) return false;

		$temp = mb_convert_encoding($word, 'UCS-2LE', 'UTF-8');
		for ($i = 1; $i < ((strlen($temp) * 2) - 1); $i+=2)
		{
			if ((ord(substr($temp, $i, 1)))) // non-latin letters should not be converted
			{
				return false;
			}
		}

		if ($this->wordIsMarkedToNotConverted($word))
		{
			return false;
		}

		return true; // if it is not an internet address the control flow gets here
	}

	/**
	 * ConvertFinglishToFarsi::wordIsMarkedToNotConverted
	 *
	 * Description of function
	 *
	 * @param $word string Blah blah blah
	 * @return bool Blah blah blah
     */
	private function wordIsMarkedToNotConverted($word)
	{
		if (strlen($word) > 1 && ($word[0] == '/' | $word[0] == '\\'))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * ConvertFinglishToFarsi::assembleFarsiTextUsingTable
	 *
	 * Description of function
	 *
	 * @param $textWords string Blah blah blah
	 * @param $wordShouldBeConvertedToFarsi string Blah blah blah
	 * @return bool Blah blah blah
     */
	private function assembleFarsiTextUsingTable($textWords, $wordShouldBeConvertedToFarsi)
	{
		$unknownWords = array();

		$latinCaretPosition = array();
		$farsiCaretPosition = array();
		$accumulatedLatinCaretPosition = 0;
		$accumulatedFarsiCaretPosition = 0;
		array_push($latinCaretPosition, $accumulatedLatinCaretPosition);
		array_push($farsiCaretPosition, $accumulatedFarsiCaretPosition);

		$farsiText = "";
		for ($i = 0; $i < count($textWords); $i++) // look words in the table, convert them and add all together.
		{
			$lowerCaseTextWord = strtolower($textWords[$i]);
			if (array_key_exists($lowerCaseTextWord, $this->knownWordsTable) && $wordShouldBeConvertedToFarsi[$i])
			{
				$farsiText .= $this->knownWordsTable[$lowerCaseTextWord];
				$latinFromFarsiWordsTable[$this->knownWordsTable[$lowerCaseTextWord]] = $lowerCaseTextWord;
				
				// caret position mapping
				$accumulatedLatinCaretPosition += strlen($lowerCaseTextWord);
				$accumulatedFarsiCaretPosition += strlen($this->knownWordsTable[$lowerCaseTextWord]);
				array_push($latinCaretPosition, $accumulatedLatinCaretPosition);
				array_push($farsiCaretPosition, $accumulatedFarsiCaretPosition);
				
			}
			else
			{
				if ($this->wordIsMarkedToNotConverted($textWords[$i])) // words with / or \ in the beginning
				{
					$farsiText .= substr($textWords[$i], 1); // remove the first letter, which is \ or /

					/// TODO : I'm not sure about this
					if (false) // in case of single editor, make place the word with / in a table so it is not converted to farsi again, but make sure to remove / from its beginning 
					{
						$this->knownWordsTable[substr($textWords[$i], 1)] = substr($textWords[$i], 1);
					}
					
					// caret position mapping
					$accumulatedLatinCaretPosition += strlen($textWords[$i]);
					$accumulatedFarsiCaretPosition += strlen(substr($textWords[$i], 1));
					array_push($latinCaretPosition, $accumulatedLatinCaretPosition);
					array_push($farsiCaretPosition, $accumulatedFarsiCaretPosition);
					
				}
				else
				{
					if ($this->shouldBeConvertedToFarsi($textWords[$i]))
					{
						array_push($unknownWords, $textWords[$i]);
					}

					$farsiText .= $textWords[$i];

					// caret position mapping
					$accumulatedLatinCaretPosition += strlen($textWords[$i]);
					$accumulatedFarsiCaretPosition += strlen($textWords[$i]);
					array_push($latinCaretPosition, $accumulatedLatinCaretPosition);
					array_push($farsiCaretPosition, $accumulatedFarsiCaretPosition);
					
				}
			}
		
		}

		if (count($unknownWords)) $farsiText = $this->correctUnknownWordsInText($farsiText, $unknownWords);

		$farsiText = $this->attachPasvands($farsiText);
		$farsiText = $this->attachPishvands($farsiText);
		$farsiText = $this->replaceHyphenWithVirtualSpace($farsiText);
		$farsiText = $this->attachKasre($farsiText);

		if ($this->useFarsiNumbers)
		{
			$farsiText = $this>replaceWithFarsiNumbers($farsiText);
		}
		
		return $farsiText;
	}

	/**
	 * ConvertFinglishToFarsi::correctUnknownWordsInText
	 *
	 * Description of function
	 *
	 * @param $farsiText string Farsi text containing english words
	 * @param $finglishWords array English Words
	 * @return string Blah Blah Blah
     */
	private function correctUnknownWordsInText($farsiText, $finglishWords)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'http://www.behnevis.com/php/convert.php');
		curl_setopt($ch, CURLOPT_USERAGENT, 'TweetIt.mobi / Thank you Behnevis');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'farsi='.urlencode(implode(' ', $finglishWords)).'&responsetime=-1&resulttype=json');
		$result = strstr(curl_exec($ch), '{');
		curl_close($ch);

		$array = json_decode($result, true);

		if ($array === NULL)
			return $farsiText;

		uasort($array, array($this, 'SizeSort'));

		return str_replace(array_keys($array), array_values($array), $farsiText);
	}

	/**
	 * ConvertFinglishToFarsi::replaceWithFarsiNumbers
	 *
	 * Replaces english numbers with persian numbers
	 *
	 * @param $text string Text containing english numbers
	 * @return string Text with converted numbers
     */
	private function replaceWithFarsiNumbers($text)
    {
        $farsi_array = array("۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹");
        $english_array = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
        return str_replace($english_array, $farsi_array, $text);
    }

	/**
	 * ConvertFinglishToFarsi::attachKasre
	 *
	 * Replace kasre between two spaces to just kasrre, hence attaching it to the word before
	 *
	 * @param $farsiTtext string Blah Blah Blah
	 * @return string Blah Blah Blah
     */
	private function attachKasre($farsiText)
	{
		return str_replace(" " . urldecode('%D9%90'), urldecode('%D9%90') . " ", $farsiText); // needs to be more restriced so it does not replace all 'ha's
	}

	/**
	 * ConvertFinglishToFarsi::attachPasvands
	 *
	 * Replace space between some 'pasvand's and their word
	 *
	 * @param $farsiTtext string Blah Blah Blah
	 * @return string Blah Blah Blah
     */
	private function attachPasvands($farsiText)
	{
		$pasvands = array("ترین","ها","هایم","هایت","هایشان","یی‌","هایش","هایت","هایتان", "هایمان","ای","هاشون","هاتون", "هات", "های", "هایی‌","ام","شان","اش");

		for ($p = 0; $p < count($pasvands); $p++)
		{
			$farsiText = str_replace(" " . $pasvands[$p] . " ", urldecode('%E2%80%8C') . $pasvands[$p] . " ", $farsiText); // needs to be more restriced so it does not replace all 'ha's
		}

		return $farsiText;
	}

	/**
	 * ConvertFinglishToFarsi::attachPishvands
	 *
	 * Replace space between some 'pishvand's and their word
	 *
	 * @param $farsiTtext string Blah Blah Blah
	 * @return string Blah Blah Blah
     */
	private function attachPishvands($farsiText)
	{
		$pishvands = array("می‌","نمی‌","بی");
		$pishvandRegexp = array('/(\sمی‌\s)/', '/(\sنمی‌\s)/', '/(\sبی\s)/');

		$farsiText = preg_replace(array('/(\sمی‌\s)/', '/(\sنمی‌\s)/', '/(\sبی\s)/'), array(" می‌" . urldecode('%E2%80%8C')," نمی‌" . urldecode('%E2%80%8C')," بی" . urldecode('%E2%80%8C')), $farsiText);

		return $farsiText;
	}

	/**
	 * ConvertFinglishToFarsi::replaceHyphenWithVirtualSpace
	 *
	 * Replace Hyphen With Virtual Space
	 *
	 * @param $s string Blah Blah Blah
	 * @return string Blah Blah Blah
     */
	private function replaceHyphenWithVirtualSpace($s)
	{
		$characters = '[ابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهیهٔآأ]';
		// replace - with virtual space
		$s = preg_replace('/(' . $characters . ')-(' . $characters . ')/', '$1' . urldecode('%E2%80%8C') . '$2', $s);

		// replace -- with -
		$s = preg_replace('/(' . $characters . ')--(' . $characters . ')/', '$1-$2', $s);

		return $s;
	}

	/**
	 * ConvertFinglishToFarsi::SizeSort
	 *
	 * Used for sorting arrays with it's value length
	 *
	 * @param $a string First value
	 * @param $a string Second value
	 * @return int 1 if a is bigger than b, else 0
     */
	private function SizeSort($a, $b)
	{
		return (strlen($a) < strlen($b));
	}
}
?>