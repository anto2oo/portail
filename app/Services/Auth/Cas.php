<?php

namespace App\Services\Auth;

use Ginger;
use App\Models\User;
use App\Models\AuthCas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Session;

class Cas extends BaseAuth
{
	protected $name = 'cas';
	private $casURL;

	public function __construct() {
		$this->config = config("auth.services." . $this->name);
		$this->casURL = config('portail.cas.url');
	}

	public function showLoginForm(Request $request) {
		if (Auth::guard('cas')->check())
			return redirect()->route('cas.request');
		else
			return parent::showLoginForm($request);
	}

	public function login(Request $request) {
		$ticket = $request->query('ticket');

		if (!isset($ticket) || empty($ticket))
			return $this->error($request, null, null, 'Ticket CAS invalide');

		$data = file_get_contents($this->casURL.'serviceValidate?service='.route('login.process', ['provider' => $this->name]).'&ticket='.$ticket);

		if (empty($data))
			return $this->error($request, null, null, 'Aucune information reçue du CAS');

		$parsed = new xmlToArrayParser($data);

		if (!isset($parsed->array['cas:serviceResponse']['cas:authenticationSuccess']))
			return $this->error($request, null, null, 'Données du CAS reçues invalides');

		$login = $parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'];

		$ginger = Ginger::user($login);

		// Renvoie une erreur différente de la 200. On passe par le CAS.
		if (!$ginger->exists() || $ginger->getResponseCode() !== 200) {
			list($login, $email, $firstname, $lastname) = [
				$parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'],
				$parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:attributes']['cas:mail'],
				$parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:attributes']['cas:givenName'],
				$parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:attributes']['cas:sn'],
			];
		}
		else {
			// Sinon par Ginger. On regarde si l'utilisateur existe ou non et on le crée ou l'update
			list($login, $email, $firstname, $lastname) = [
				$ginger->getLogin(),
				$ginger->getEmail(),
				$ginger->getFirstname(),
				$ginger->getLastname(),
			];
		}

		if (($cas = AuthCas::findByEmail($email)))
			$user = $cas->user;
		else {
			$user = $this->updateOrCreateUser(compact('email', 'firstname', 'lastname'));
			$cas = $this->createAuth($user->id, compact('login', 'email'));
		}

		if (!$user->isActive())
			return $this->error($request, $user, $userAuth, 'Ce compte a été désactivé');

		// On vérifie qu'on a bien lié son CAS à une connexion email/mot de passe
		if ($user->password()->exists())
			return $this->connect($request, $user, $cas);
		else {
			Auth::guard('cas')->login($user);
			Session::updateOrCreate(['id' => \Session::getId()], ['auth_provider' => $this->name]);

			return redirect()->route('cas.request');
		}
	}

	public function register(Request $request) {
		return redirect()->route('register.show', ['redirect' => $request->query('redirect', url()->previous())])->cookie('auth_provider', '', config('portail.cookie_lifetime'));
	}

	/*
	 * Redirige vers la bonne page en cas de succès
	 */
	protected function success(Request $request, $user = null, $userAuth = null, $message = null) {
		if (!$userAuth->is_active) {
			$userAuth->is_active = 1;
			$userAuth->save();

			$message = 'Vous êtes maintenant considéré.e comme un.e étudiant.e';
		}

		return parent::success($request, $user, $userAuth, $message);
	}

	/**
	 * Se déconnecte du CAS de l'UTC
	 */
	public function logout(Request $request) {
		return redirect(config('portail.cas.url').'logout');
	}
}





class xmlToArrayParser
{
	/** The array created by the parser can be assigned to any variable: $anyVarArr = $domObj->array.*/
	public  $array = array();
	public  $parse_error = false;
	private $parser;
	private $pointer;

	/** Constructor: $domObj = new xmlToArrayParser($xml); */
	public function __construct($xml) {
		$this->pointer =& $this->array;
		$this->parser = xml_parser_create("UTF-8");
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($this->parser, "tag_open", "tag_close");
		xml_set_character_data_handler($this->parser, "cdata");
		$this->parse_error = xml_parse($this->parser, ltrim($xml))? false : true;
	}

	/** Free the parser. */
	public function __destruct() { xml_parser_free($this->parser);}

	/** Get the xml error if an an error in the xml file occured during parsing. */
	public function get_xml_error() {
		if($this->parse_error) {
			$errCode = xml_get_error_code ($this->parser);
			$thisError =  "Error Code [". $errCode ."] \"<strong style='color:red;'>" . xml_error_string($errCode)."</strong>\",
			at char ".xml_get_current_column_number($this->parser) . "
			on line ".xml_get_current_line_number($this->parser)."";
		}else $thisError = $this->parse_error;
		return $thisError;
	}

	private function tag_open($parser, $tag, $attributes) {
		$this->convert_to_array($tag, 'attrib');
		$idx = $this->convert_to_array($tag, 'cdata');
		if (isset($idx)) {
			$this->pointer[$tag][$idx] = Array('@idx' => $idx,'@parent' => &$this->pointer);
			$this->pointer =& $this->pointer[$tag][$idx];
		} else {
			$this->pointer[$tag] = Array('@parent' => &$this->pointer);
			$this->pointer =& $this->pointer[$tag];
		}
		if (!empty($attributes)) { $this->pointer['attrib'] = $attributes; }
	}

	/** Adds the current elements content to the current pointer[cdata] array. */
	private function cdata($parser, $cdata) { $this->pointer['cdata'] = trim($cdata); }

	private function tag_close($parser, $tag) {
		$current = & $this->pointer;
		if(isset($this->pointer['@idx'])) {unset($current['@idx']);}

		$this->pointer = & $this->pointer['@parent'];
		unset($current['@parent']);

		if(isset($current['cdata']) && count($current) == 1) { $current = $current['cdata'];}
		else if(empty($current['cdata'])) {unset($current['cdata']);}
	}

	/** Converts a single element item into array(element[0]) if a second element of the same name is encountered. */
	private function convert_to_array($tag, $item) {
		if (isset($this->pointer[$tag][$item])) {
			$content = $this->pointer[$tag];
			$this->pointer[$tag] = array((0) => $content);
			$idx = 1;
		} else if (isset($this->pointer[$tag])) {
			$idx = count($this->pointer[$tag]);
			if (!isset($this->pointer[$tag][0])) {
				foreach ($this->pointer[$tag] as $key => $value) {
					unset($this->pointer[$tag][$key]);
					$this->pointer[$tag][0][$key] = $value;
				}
			}
		} else
			$idx = null;
		return $idx;
	}
}
