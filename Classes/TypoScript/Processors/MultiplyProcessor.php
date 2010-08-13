<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript\Processors;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Processor that multiplies a given number or numeric string with the given factor.
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MultiplyProcessor implements \F3\TypoScript\ProcessorInterface {

	/**
	 * The factor to multiply the subject with
	 * @var integer
	 */
	protected $factor = NULL;

	/**
	 * @param integer $factor the number of digits after the decimal point. Negative values are also supported. (-1 multiplys to full 10ths)
	 * @return void
	 */
	public function setFactor($factor) {
		$this->factor = $factor;
	}

	/**
	 * @return integer the number of digits after the decimal point. Negative values are also supported. (-1 multiplys to full 10ths)
	 */
	public function getFactor() {
		return $this->factor;
	}

	/**
	 * Multiplies a given number or numeric string $subject with $factor.
	 *
	 * @param float/string $subject The subject to multiply.
	 * @return float The multiplied value ($subject*$this->factor)
	 * @author Sebastian Kurf�rst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function process($subject) {
		if (!is_numeric($subject)) throw new \F3\TypoScript\Exception('Expected a numeric string as first parameter.', 1224146988);
		if (!is_float($this->factor) && !is_int($this->factor)) throw new \F3\TypoScript\Exception('Expected a float as second parameter.', 1224146995);
		$subject = floatval($subject);
		return $subject * $this->factor;
	}
}
?>
