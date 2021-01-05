<?php
/**
 * XML-RPC decoder
 *
 * @package     Comodojo Spare Parts
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @license     MIT
 * @link        https://github.com/comodojo/xmlrpc/
 *
 * LICENSE:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class XmlrpcDecoder {

	private $is_fault = false;

	public function __construct() {

		libxml_use_internal_errors(true);
	}

	/**
	 * Decode an xmlrpc response
	 *
	 * @param   string  $response
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 */
	public function decodeResponse($response) {

		$xml_data = simplexml_load_string($response);

		if ( $xml_data === false ) throw new Exception("Not a valid XMLRPC response");

		$data = array();

		try {

			if ( isset($xml_data->fault) ) {

				$this->is_fault = true;

				array_push($data, $this->decodeValue($xml_data->fault->value));

			} else if ( isset($xml_data->params) ) {

				foreach ( $xml_data->params->param as $param ) array_push($data, $this->decodeValue($param->value));

			} else throw new Exception("Uncomprensible response");

		} catch (Exception $xe) {

			throw $xe;
		}

		return isset($data[0]) ? $data[0] : $data;
	}

	public function isFault() {

		return $this->is_fault;
	}

	/**
	 * Decode a value from xmlrpc data
	 *
	 * @param   mixed   $value
	 *
	 * @return  mixed
	 *
	 * @throws  \Exception
	 */
	private function decodeValue($value) {

		$children = $value->children();

		if ( count($children) != 1 ) throw new Exception("Cannot decode value: invalid value element");

		$child = $children[0];

		$child_type = $child->getName();

		switch ( $child_type ) {

			case "i4":
			case "int":
				$return_value = $this->decodeInt($child);
			break;

			case "double":
				$return_value = $this->decodeDouble($child);
			break;

			case "boolean":
				$return_value = $this->decodeBool($child);
			break;

			case "base64":
				$return_value = $this->decodeBase($child);
			break;

			case "dateTime.iso8601":
				$return_value = $this->decodeIso8601Datetime($child);
			break;

			case "string":
				$return_value = $this->decodeString($child);
			break;

			case "array":
				$return_value = $this->decodeArray($child);
			break;

			case "struct":
				$return_value = $this->decodeStruct($child);
			break;

			case "nil":
			case "ex:nil":
				$return_value = $this->decodeNil();
			break;

			default:
				throw new Exception("Cannot decode value: invalid value type");
			break;
		}

		return $return_value;
	}

	/**
	 * Decode an XML-RPC <base64> element
	 */
	private function decodeBase($base64) {

		return base64_decode($this->decodeString($base64));
	}

	/**
	 * Decode an XML-RPC <boolean> element
	 */
	private function decodeBool($boolean) {

		return filter_var($boolean, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Decode an XML-RPC <dateTime.iso8601> element
	 */
	private function decodeIso8601Datetime($date_time) {

		return strtotime($date_time);
	}

	/**
	 * Decode an XML-RPC <double> element
	 */
	private function decodeDouble($double) {

		return (double) ($this->decodeString($double));
	}

	/**
	 * Decode an XML-RPC <int> or <i4> element
	 */
	private function decodeInt($int) {

		return filter_var($int, FILTER_VALIDATE_INT);
	}

	/**
	 * Decode an XML-RPC <string>
	 */
	private function decodeString($string) {

		return (string) $string;
	}

	/**
	 * Decode an XML-RPC <nil/>
	 */
	private function decodeNil() {

		return null;
	}

	/**
	 * Decode an XML-RPC <struct>
	 */
	private function decodeStruct($struct) {

		$return_value = array();

		foreach ( $struct->member as $member ) {

			$name = $member->name."";
			$value = $this->decodeValue($member->value);
			$return_value[$name] = $value;
		}

		return $return_value;
	}

	/**
	 * Decode an XML-RPC <array> element
	 */
	private function decodeArray($array) {

		$return_value = array();

		foreach ( $array->data->value as $value ) {

			$return_value[] = $this->decodeValue($value);
		}

		return $return_value;
	}
}
