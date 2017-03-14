<?php

/**
 * Parsedown Extended adds some additional block definitions to the Parsedown class.
 *
 * @package Pdi
 */
require_once dirname( __FILE__ ) . '/inc/Parsedown.php';

class ParsedownExtended extends Parsedown {
	function __construct() {

		// redefines the left-bracket ('[') block type so that the ParsedownExtended::blockCheckbox()
		// handler will be called to deal with checkbox tags
		$this->BlockTypes['['] = array( 'Reference', 'Checkbox' );
	}

	/**
	 * Replaces checkbox tags with inline checkbox-inputs.
	 *
	 * @param array $Line The line.
	 * @return return The checkbox-input block.
	 */
	protected function blockCheckbox( array $Line ) {
		if ( preg_match( '/^[ ]{0,3}\[(x| ?)\] (.*)$/mi', $Line['text'], $matches ) ) {
			$checked = 'x' === strtolower( $matches[1] ) ? true : false;

			// defines the outer block element
			$Block = array(
				'element' => array(
					'name' => 'div',
					'handler' => 'inlineElements' // calls the ParsedownExtended::inlineElements()
					                              // handler, which calls the respective handlers
					                              // of the child elements
				)
			);

			// defines an input of type checkbox, which will be handled by Parsedown::element()
			$Block['element']['text'][] = array(
				'name' => 'input',
				'handler' => 'element',
				'attributes' => array(
					'type' => 'checkbox',
				)
			);

			// retroactively checks the checkbox input defined above (if the checkbox tag contained
			// an 'x', the checkbox input should be checked)
			if ( $checked ) {
				$Block['element']['text'][0]['attributes']['checked'] = 'checked';
			}

			// defines a span to contain the text related to the checkbox
			$Block['element']['text'][] = array(
				'name' => 'span',
				'handler' => 'line',
				'text' => $matches[2]
			);

			return $Block;
		}
	}

	/**
	 * For elements that shouldn't be separated by new lines.
	 *
	 * @param array $Elements The elements.
	 * @return return The modified markup.
	 */
	protected function inlineElements( array $Elements ) {
		$markup = '';

		foreach ( $Elements as $Element ) {
			$markup .= $this->element( $Element );
		}

		return $markup;
	}
}
