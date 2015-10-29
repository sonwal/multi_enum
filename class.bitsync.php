<?php
/**
 * Manish Sonwal
 * 30-9-2015
 *
 * Class to convert flags to bits (and vice-versa) for a grouped input format. Class extends bitmap.
 *
 *****************************************************
 * :: USE CASES ::
 *
 * $configFlags = array(
 * 		"Price" => "<>=",
 *		"TP" => "GSE",
 *		"APPLICABLE" => "DWAMI",
 * 	);
 *
 * 1) Input flags (string)
 * 	$inputFlagArr = array(
 *		"Price" => "<",
 *		"TP" => "G",
 *		"APPLICABLE" => "DW"
 *	);
 *
 * $bitSyncObj = new bitsync($configFlags,$inputFlagArr,bitsync::STRING_LITERAL);
 * $response = $bitSyncObj->convert();
 *
 * 2) Input numbers (integers)
 *
 * $inputInt = 1656;
 * $bitSyncObj = new bitsync($configFlags,$inputInt,bitsync::INT_LITERAL);
 * $response = $bitSyncObj->convert();
 *
 */

if (!class_exists("bitmap"))
	require_once ("class.bitmap.php");

class bitsync extends bitmap {
	public $flagSeqFinalStr = "";
	private $inputFlagArr = array();
	private $flagSeqArr = array();
	private $inputInt = 0;
	private $existingValue = 0;
	// for flag array input. To prevent overwriting existing other groups.

	const ALLOWED_FLAGS_ERROR = "ALLOWED_FLAGS";
	const INPUT_FLAGS_ERROR = "INPUT";
	const TYPE_FLAGS_ERROR = "TYPE_FLAGS";
	const EXISTING_VALUE_ERROR = "EXISTING_FLAGS";
	const DATA_ERRORS = "DATA";

	const ERROR_FLAGS_NOT_ARRAY = "Error: input flags are not in array format.";
	const ERROR_FLAGS_NOT_ONE_DIM_ARRAY = "Error: input flags are not in one dimentional array format.";
	const ERROR_FLAGS_EMPTY = "Error: input flags are empty.";
	const ERROR_INPUT_NOT_ARRAY = "Error: input values are not array.";
	const ERROR_EMPTY_TYPE = "Error: type value cannot be empty.";
	const ERROR_TYPE_NOT_SUPPORTED = "Error: type value is unsupported.";
	const ERROR_GROUP_NOT_FOUND = "Error: group not found in input.";
	const ERROR_MISMATCHED_FLAG_IN_GROUP = "Error: Invalid flags found for group ";
	const ERROR_EXISTING_VALUE_UNSUPPORTED = "Error: Existing value is not supported.";

	/*Main construct for entering values for conversion.*/
	public function __construct($flagSeqArr, $dataInput, $type = self::STRING_LITERAL, $existingValue = 0) {
		$this -> validateFlagInput($flagSeqArr);
		$this -> validateType($type);

		switch($type) {
			case self::INT_LITERAL :
				$this -> inputInt = $dataInput;
				break;
			default :
				$this -> validateValueInput($dataInput);
				$this -> validateExistingInt($existingValue);
				break;
		}
	}

	/*Function to actually convert values.*/
	public function convert() {
		if (!empty($this -> errors))
			return $this -> errors;

		$result = array();
		switch ($this->type) {
			case self::STRING_LITERAL :
				// converting existing values to flag first.
				parent::__construct($this -> flagSeqFinalStr, $this -> existingValue, self::INT_LITERAL);
				$tempResult = parent::convert();
				if (is_array($tempResult)) {
					$result[self::RESULT_ERROR][self::DATA_ERRORS] = $tempResult;
					return $result;
				}

				// imposing new values to old flag array.
				$tempResult = $this -> convertFlagsToGroups($tempResult);
				$this -> inputFlagArr = array_replace($tempResult, $this -> inputFlagArr);

				$input = implode("", array_reverse($this -> inputFlagArr));
				parent::__construct($this -> flagSeqFinalStr, $input, self::STRING_LITERAL);
				$tempResult = parent::convert();

				if (!is_array($tempResult))
					$result[self::RESULT_SUCCESS] = $tempResult;
				else
					$result[self::RESULT_ERROR][self::DATA_ERRORS] = $tempResult;
				return $result;
				break;
			case self::INT_LITERAL :
				parent::__construct($this -> flagSeqFinalStr, $this -> inputInt, $this -> type);
				$tempResult = parent::convert();
				if (!is_array($tempResult))
					$result[self::RESULT_SUCCESS] = $this -> convertFlagsToGroups($tempResult);
				else
					$result[self::RESULT_ERROR][self::DATA_ERRORS] = $tempResult;
				return $result;
				break;
		}
	}

	private function validateFlagInput($flagSeqArr) {
		if (empty($flagSeqArr))
			$this -> errors[self::RESULT_ERROR][self::ALLOWED_FLAGS_ERROR][] = self::ERROR_FLAGS_EMPTY;
		else {
			if (!is_array($flagSeqArr))
				$this -> errors[self::RESULT_ERROR][self::ALLOWED_FLAGS_ERROR][] = self::ERROR_FLAGS_NOT_ARRAY;
			else {
				if ($this -> isArrMulti($flagSeqArr))
					$this -> errors[self::RESULT_ERROR][self::ALLOWED_FLAGS_ERROR][] = self::ERROR_FLAGS_NOT_ONE_DIM_ARRAY;

				$this -> flagSeqArr = $flagSeqArr;
				$this -> flagSeqFinalStr = implode('', array_reverse($flagSeqArr));
				$tempArr = str_split($this -> flagSeqFinalStr);
				$tempArr = array_map("strtoupper", $tempArr);
				if ($this -> has_duplicates($tempArr))
					$this -> errors[self::RESULT_ERROR][self::ALLOWED_FLAGS_ERROR][] = self::ERROR_DUPLICATES_INPUT;
			}
		}
	}

	private function validateValueInput($dataArr) {
		if (empty($dataArr))
			$this -> errors[self::RESULT_ERROR][self::INPUT_FLAGS_ERROR][] = self::ERROR_EMPTY_INPUT;

		if (!is_array($dataArr))
			$this -> errors[self::RESULT_ERROR][self::INPUT_FLAGS_ERROR][] = self::ERROR_INPUT_NOT_ARRAY;
		else {
			// mapping data with flags, find duplicates and misplaced input.
			$flagKeys = array_keys($this -> flagSeqArr);
			foreach ($dataArr as $key => $value) {
				if (!in_array($key, $flagKeys))
					$this -> errors[self::RESULT_ERROR][self::INPUT_FLAGS_ERROR][$key][] = self::ERROR_GROUP_NOT_FOUND;
				else
					$this -> checkMisplacedFlags($key, $this -> flagSeqArr[$key], $value);
			}
		}

		foreach ($this->flagSeqArr as $key => $value) {
			if (is_array($value))// not handling this case now. Not allowed. Throwing error.
				$this -> errors[self::RESULT_ERROR][self::INPUT_FLAGS_ERROR][$key][] = self::ERROR_INVALID_INPUT_ARRAY;
		}
		$this -> inputFlagArr = $dataArr;
	}

	private function validateType($type) {
		if (empty($type))
			$this -> errors[self::RESULT_ERROR][self::TYPE_FLAGS_ERROR][] = self::ERROR_EMPTY_TYPE;

		if ($type != self::STRING_LITERAL && $type != self::INT_LITERAL)
			$this -> errors[self::RESULT_ERROR][self::TYPE_FLAGS_ERROR][] = self::ERROR_TYPE_NOT_SUPPORTED;
		$this -> type = $type;
	}

	private function checkMisplacedFlags($flagKey, $flagDeepArr, $inputDeepArr, $innerKey = "") {
		$flagArr = str_split($flagDeepArr);
		$inputArr = str_split($inputDeepArr);
		$temp = array();

		foreach ($inputArr as $value) {
			if (!empty($value) && !in_array($value, $flagArr)) {
				$temp[] = self::ERROR_MISMATCHED_FLAG_IN_GROUP;
				break;
			}
		}

		if (!empty($temp)) {
			if ($innerKey === 0 || !empty($innerKey))
				$this -> errors[self::RESULT_ERROR][self::INPUT_FLAGS_ERROR][$flagKey][$innerKey] = $temp;
			else
				$this -> errors[self::RESULT_ERROR][self::INPUT_FLAGS_ERROR][$flagKey] = $temp;
		}
	}

	private function convertFlagsToGroups($flags) {
		$finalArr = array();
		$flags = str_split($flags);
		foreach ($this -> flagSeqArr as $group => $groupFlags) {
			$groupFlag = str_split($groupFlags);
			$finalArr[$group] = "";
			foreach ($groupFlag as $flag) {
				if (in_array($flag, $flags))
					$finalArr[$group] .= $flag;
			}
		}
		return $finalArr;
	}

	private function validateExistingInt($int) {
		if ($int == "")
			$this -> errors[self::RESULT_ERROR][self::EXISTING_VALUE_ERROR][] = self::ERROR_EXISTING_VALUE_UNSUPPORTED;

		// validate int input.
		if (!preg_match("/^[0-9]+$/", $int))
			$this -> errors[self::RESULT_ERROR][self::EXISTING_VALUE_ERROR][] = self::ERROR_BITS_INPUT_INVALID_INT;
		$this -> existingValue = $int;
	}

}
?>