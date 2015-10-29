<?php
/*
 * Manish Sonwal
 * Class for char to int (and vice-versa) operations for multiple enum scenerio.
 * Updated for array input usage for multiple conversions at a time.
 * 18-6-2015
 * */

class bitmap {
	const ON_FLAG_BIT = 1;
	const OFF_FLAG_BIT = 0;

	const STRING_LITERAL = "string";
	const INT_LITERAL = "int";

	const RESULT_ERROR = "errors";
	const RESULT_SUCCESS = "success";

	const ARRAY_KEY = "key";

	const ARRAY_INPUT_STRING = "inputString";
	const ARRAY_INPUT_STRING_LEN = "inputStringLen";
	const ARRAY_INPUT_CHARS_ARR = "inputCharsArr";

	const ARRAY_INPUT_INT = "actualInputInt";
	const ARRAY_INPUT_BITS = "inputBits";
	const ARRAY_INPUT_BITS_LEN = "inputBitsLen";
	const ARRAY_INPUT_BITS_ARR = "inputBitsArr";

	public $flag_seq_str;

	const ERROR_EMPTY_INPUT = "Error: Empty Input";
	const ERROR_STRING_INPUT_SIZE = "Error: Input value is longer than ";
	const ERROR_DUPLICATES_INPUT = "Error: Input value contains duplicates.";
	const ERROR_INPUT_INVALID_FLAGS = "Error: Input contains invalid flags.";
	const ERROR_BITS_INPUT_SIZE = "Error: Input binary value should be equals to ";
	const ERROR_BITS_INPUT_INVALID = "Error: Input value is not in valid binary format.";
	const ERROR_BITS_INPUT_INVALID_INT = "Error: Input value is not a valid integer.";
	const ERROR_INVALID_INPUT_ARRAY = "Error: Input array should be one dimentional vector (array) or scaler (int/string)";

	// Type of input.
	protected $type = "";

	protected $errors = array();

	// input string.
	private $inputString = "";

	// length of input string.
	private $inputStringLen = 0;

	// length of valid chars.
	private $validStringLen = 0;

	// array of valid chars.
	private $validCharsArr = array();

	// array of input chars.
	private $inputCharsArr = array();

	// arranged input string.
	private $arrangedInput = "";

	// derived bits.
	private $bits;

	// input bits.
	private $inputBits;

	// inpup bits length.
	private $inputBitsLen = 0;

	// input bit arr.
	private $inputBitsArr = array();

	// derived string
	private $derivedStr = "";

	// array input data
	private $arrInputData = array();

	// input type array/string
	private $inputDataTypeArray = FALSE;

	// main multi array output
	private $mainMultiResult = array();

	// actual input int
	private $actualInputInt = 0;

	public function __construct($flag_seq_str, $data, $type = self::STRING_LITERAL) {
		$this -> flag_seq_str = $flag_seq_str;
		$this -> validStringLen = strlen($this -> flag_seq_str);
		$this -> validCharsArr = str_split($this -> flag_seq_str);
		$this -> inputDataTypeArray = is_array($data) ? TRUE : FALSE;
		$this -> type = $type;

		if ($this -> inputDataTypeArray) {
			if ($this -> isArrMulti($data)) {
				$this -> errors[] = self::ERROR_INVALID_INPUT_ARRAY;
				return;
			}

			foreach ($data as $key => $value) {
				switch($type) {
					case self::STRING_LITERAL :
						$this -> inputString = strtoupper($value);
						$this -> inputStringLen = strlen($this -> inputString);
						$this -> inputCharsArr = str_split($this -> inputString);

						$this -> arrInputData[] = array(self::ARRAY_KEY => $key, self::ARRAY_INPUT_STRING => $this -> inputString, self::ARRAY_INPUT_STRING_LEN => $this -> inputStringLen, self::ARRAY_INPUT_CHARS_ARR => $this -> inputCharsArr);
						break;
					case self::INT_LITERAL :
						$this -> actualInputInt = $value;
						$this -> inputBits = $this -> convertIntToBits($value);
						$this -> inputBitsLen = strlen($this -> inputBits);
						$this -> inputBitsArr = str_split($this -> inputBits);

						$this -> arrInputData[] = array(self::ARRAY_KEY => $key, self::ARRAY_INPUT_INT => $this -> actualInputInt, self::ARRAY_INPUT_BITS => $this -> inputBits, self::ARRAY_INPUT_BITS_LEN => $this -> inputBitsLen, self::ARRAY_INPUT_BITS_ARR => $this -> inputBitsArr);
						break;
				}
			}
		} else {
			switch($type) {
				case self::STRING_LITERAL :
					$this -> inputString = strtoupper($data);
					$this -> inputStringLen = strlen($this -> inputString);
					$this -> inputCharsArr = str_split($this -> inputString);
					break;
				case self::INT_LITERAL :
					$this -> actualInputInt = $data;
					$this -> inputBits = $this -> convertIntToBits($data);
					$this -> inputBitsLen = strlen($this -> inputBits);
					$this -> inputBitsArr = str_split($this -> inputBits);
					break;
			}
		}
	}

	// Main Function to convert data.
	public function convert() {
		if ($this -> inputDataTypeArray) {
			if (!empty($this -> errors))
				return $this -> errors;

			$this -> mainMultiResult = array();
			foreach ($this -> arrInputData as $value) {
				$this -> derivedStr = $this -> bits = "";

				switch($this->type) {
					case self::STRING_LITERAL :
						$this -> inputString = $value[self::ARRAY_INPUT_STRING];
						$this -> inputStringLen = $value[self::ARRAY_INPUT_STRING_LEN];
						$this -> inputCharsArr = $value[self::ARRAY_INPUT_CHARS_ARR];

						$res = $this -> validateInput();
						if (!empty($res) && is_array($res))
							$this -> mainMultiResult[self::RESULT_ERROR][$value[self::ARRAY_KEY]] = $res;
						else {
							$this -> convertToBits();
							$this -> mainMultiResult[self::RESULT_SUCCESS][$value[self::ARRAY_KEY]] = $this -> convertBitToInt();
						}
						break;
					case self::INT_LITERAL :
						$this -> actualInputInt = $value[self::ARRAY_INPUT_INT];
						$this -> inputBits = $value[self::ARRAY_INPUT_BITS];
						$this -> inputBitsLen = $value[self::ARRAY_INPUT_BITS_LEN];
						$this -> inputBitsArr = $value[self::ARRAY_INPUT_BITS_ARR];

						$res = $this -> validateBinInput();
						if (!empty($res) && is_array($res))
							$this -> mainMultiResult[self::RESULT_ERROR][$value[self::ARRAY_KEY]] = $res;
						else
							$this -> mainMultiResult[self::RESULT_SUCCESS][$value[self::ARRAY_KEY]] = $this -> convertToString();
						break;
				}
				$this -> errors = array();
			}
			$this -> errors = array();
			return $this -> mainMultiResult;
		} else {
			$this -> errors = array();
			$this -> derivedStr = $this -> bits = "";
			switch($this->type) {
				case self::STRING_LITERAL :
					$res = $this -> validateInput();
					if (!empty($res) && is_array($res))
						return $res;
					$this -> convertToBits();
					return $this -> convertBitToInt();
					break;
				case self::INT_LITERAL :
					$res = $this -> validateBinInput();
					if (!empty($res) && is_array($res))
						return $res;
					return $this -> convertToString();
					break;
			}
			$this -> errors = array();
		}
	}

	/*
	 * String to bits conversion functions.
	 */
	private function validateInput() {
		if (empty($this -> inputString)) {
			$this -> errors[] = self::ERROR_EMPTY_INPUT;
			return $this -> errors;
		}

		// validate string length.
		if (empty($this -> inputStringLen) || $this -> inputStringLen > $this -> validStringLen)
			$this -> errors[] = self::ERROR_STRING_INPUT_SIZE . $this -> validStringLen . " chars.";

		// validate duplicate chars.
		$var_duplicates = $this -> has_duplicates($this -> inputCharsArr);
		if (!empty($var_duplicates))
			$this -> errors[] = self::ERROR_DUPLICATES_INPUT;

		// Matching input flags with valid flags.
		$errorMatch = FALSE;
		foreach ($this -> inputCharsArr as $value) {
			if (!in_array($value, $this -> validCharsArr)) {
				$errorMatch = TRUE;
				break;
			}
		}

		if ($errorMatch)
			$this -> errors[] = self::ERROR_INPUT_INVALID_FLAGS;

		if (!empty($this -> errors))
			return $this -> errors;
		return TRUE;
	}

	private function convertToBits() {
		if (empty($this -> inputString) || !empty($this -> errors))
			return FALSE;

		// arranging flags.
		foreach ($this -> validCharsArr as $value) {
			if (in_array($value, $this -> inputCharsArr)) {
				$this -> bits .= "" . self::ON_FLAG_BIT;
				$this -> arrangedInput .= $value;
			} else
				$this -> bits .= "" . self::OFF_FLAG_BIT;
		}
		return $this -> bits;
	}

	/*
	 * Bits to string conversion functions.
	 */

	private function validateBinInput() {
		if ($this -> actualInputInt === "") {
			$this -> errors[] = self::ERROR_EMPTY_INPUT;
			return $this -> errors;
		}

		// validate int input.
		if (!preg_match("/^[0-9]+$/", $this -> actualInputInt))
			$this -> errors[] = self::ERROR_BITS_INPUT_INVALID_INT;

		// validate binary length
		if (empty($this -> inputBitsLen) || $this -> inputBitsLen != $this -> validStringLen)
			$this -> errors[] = self::ERROR_BITS_INPUT_SIZE . $this -> validStringLen . " bits.";

		// validate binary data.
		if (!preg_match("/^[0-1]+$/", $this -> inputBits))
			$this -> errors[] = self::ERROR_BITS_INPUT_INVALID;

		if (!empty($this -> errors))
			return $this -> errors;
		return TRUE;
	}

	private function convertToString() {
		if (empty($this -> inputBits) || !empty($this -> errors))
			return FALSE;

		// converting bits to string.
		foreach ($this -> inputBitsArr as $key => $value) {
			if (!empty($value))
				$this -> derivedStr .= $this -> validCharsArr[$key];
		}
		return $this -> derivedStr;
	}

	/*
	 * Miscellaneous functions.
	 */

	private function convertBitToInt() {
		if (empty($this -> bits))
			return FALSE;

		return bindec($this -> bits);
	}

	private function convertIntToBits($int) {
		if ($int === "")
			return FALSE;

		return sprintf("%0" . $this -> validStringLen . "d", decbin($int));
	}

	protected function isArrMulti($arr) {
		return (count($arr) != count($arr, TRUE));
	}

	protected function has_duplicates($array) {
		return count(array_keys(array_flip($array))) !== count($array);
	}

}
?>