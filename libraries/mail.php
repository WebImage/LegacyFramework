<?php

interface IMailMessagePart {
	public function toString($boundary);
}
class MessageBody implements IMailMessagePart {
	private $messageBody;
	function __construct($message_body) {
		$this->messageBody = $message_body;
	}
	function toString($boundary) {
		$message_string = '';//'--' . $boundary . "\n";
		$message_string .= 'Content-Type: text/plain' . "\n\n";
		$message_string .= $this->messageBody . "\n\n";
		return $message_string;
	}
}
class HtmlAltMessageBody extends MessageBody {
	private $html, $text;
	function __construct($html, $plain_text) {
		$this->html = $html;
		$this->text = $plain_text;
	}
	function toString($boundary) {
		$alt_boundary = 'alt-' . $boundary;
		$message_string = '';
		$message_string .= 'Content-Type: multi-part/alternative; boundary="' . $alt_boundary . '"' . "\n";
		
		// PLAIN TEXT
		$message_string .= "\n";
		$message_string .= '--' . $alt_boundary . "\n";
		$message_string .= 'Content-Type: text/plain; charset=UTF-8' . "\n";
		$message_string .= 'Content-Transfer-Encoding: 8bit' . "\n\n";
		$message_string .= $this->text . "\n\n";
		
		// HTML
		
		$message_string .= '--' . $alt_boundary . "\n";
		$message_string .= 'Content-Type: text/html; charset=UTF-8' . "\n";
		$message_string .= 'Content-Transfer-Encoding: 8bit' . "\n\n";
		$message_string .= $this->html . "\n\n";
		
		$message_string .= '--' . $alt_boundary . '--';
		return $message_string;
	}
}

class MailAttachment implements IMailMessagePart {
	public $fileContents, $fileName, $mimeType, $disposition;
	
	function __construct($file_contents=null, $file_name=null, $mime_type=null, $disposition=null) {
		if (empty($mime_type)) $mime_type = 'application/octet-stream';
		if (empty($disposition)) $disposition = 'attachment';
		$this->setFileContents($file_contents);
		$this->setFileName($file_name);
		$this->setMimeType($mime_type);
		$this->setDisposition($disposition);
	}
	function toString($boundary_string) {
		$attachment_string = '--' . $boundary_string . "\n";
		$attachment_string .= 'Content-Type: ' . $this->getMimeType() . '; name="' . $this->getFileName() . '"' . "\n";
		$attachment_string .= 'Content-Transfer-Encoding: base64' . "\n";
		$attachment_string .= 'Content-Disposition: ' . $this->getDisposition() . '; filename="' . $this->getFileName() . '"' . "\n\n";
		
		$attachment_string .= chunk_split(base64_encode( $this->getFileContents() ));
		return $attachment_string;
	}
	
	function getMimeType() { return $this->mimeType; }
	function getDisposition() { return $this->disposition; }
	function getFileContents() { 
		return $this->fileContents;
	}
	function getFileName() { return $this->fileName; }
	
	function setFileContents($file_contents) {
		$this->fileContents = $file_contents;
	}
	function setMimeType($mime_type) { $this->mimeType = $mime_type; }
	function setDisposition($disposition) { $this->disposition = $disposition; }
	function setFileName($file_name) { $this->fileName = $file_name; }
}

class MailFileAttachment extends MailAttachment {
	public $filePath, $fileName;
	
	function __construct($file_path, $file_name=null, $mime_type=null, $disposition=null) {
		$this->setFilePath($file_path); // Full path, including file name
		
		$contents = file_get_contents($this->getFilePath());
		
		parent::__construct($contents, $file_name, $mime_type, $disposition);
		/*
		$this->setfileName 	= $file_name; // An alternative file name that overrides the value derived from filePath
		$this->mimeType		= $mime_type;
		$this->disposition	= $disposition;
		*/
	}
	
	function getFilePath() { return $this->filePath; }
	function getFileName() {
		$file_name = $this->fileName; 
		if (!empty($file_name)) return $file_name;
		else return basename($this->getFilePath());
	}

	function setFilePath($file_path) { $this->filePath = $file_path; }
}

class MailPerson {
	var $email, $name;
	function __construct($email, $name='') {
		$this->setEmail($email);
		$this->setName($name);
	}
	function getEmail() { return $this->email; }
	function setEmail($email) { $this->email = $email; }
	function getName() { return $this->name; }
	function setName($name) { $this->name = $name; }
	function toString() {
		$email = $this->getEmail();
		$name = $this->getName();
		if (empty($name)) return $email;
		else return $name . '<' . $email . '>';
	}
}

class MailMessage {
	// Recipients (standard, cc, bcc)
	private $recipients = array();
	private $recipientsCc = array();
	private $recipientsBcc = array();
	private $mailBoundaries = array();
	
	private $from, $replyTo, $subject, $receipt;
	
	// Message Sections / Parts (not including attachments)
	private $bodySections = array();
	private $bodyPlain;
	private $bodyHtml;
	
	// Attachments
	private $attachments = array();
	
	/** 
	 * Message Priority
	 * 1 = Highest
	 * 2 = High
	 * 3 = Normal (default)
	 * 4 = Low
	 * 5 = Lowest
	 */
	private $priority = 3;
	
	function __construct() {
		$this->setFrom(new MailPerson('', ''));
	}
	
	// Getters
	public function getRecipients() { return $this->recipients; }
	public function getRecipientsString() {
		$recipients = $this->getRecipients();
		$recipient_string_parts = array();
		foreach($recipients as $recipient) {
			array_push($recipient_string_parts, $recipient->toString());
		}
		return implode(', ', $recipient_string_parts);
	}
	
	public function getCcRecipients() { return $this->recipientsCc; }
	public function getBccRecipients() { return $this->recipientsBcc; }
	
	public function getFromString() { return $this->from->toString(); }
	
	public function getReplyToString() {
		$reply_to = '';
		if (is_object($this->replyTo) && is_a($this->replyTo, 'MailPerson')) {
			$reply_to = $this->replyTo->toString();
		}
		if (!empty($reply_to)) return $reply_to;
		else return $this->getFromString();
	}
	
	public function getSubject() { return $this->subject; }
	
	public function getAttachments() { return $this->attachments; }
	public function getNumAttachments() {
		$attachments = $this->getAttachments();
		return count($attachments);
	}
	public function hasAttachments() { return ($this->getNumAttachments() > 0); }
	
	public function getBodySections() { return $this->bodySections; }
	public function getNumBodySections() {
		$parts = $this->getBodySections();
		return count($parts);
	}
	public function hasMultipleBodySections() { return ($this->getNumBodySections() > 1); }
	
	// Utility
	private function getMailPerson($mail_person_obj) {
		if (is_object($mail_person_obj)) {
			if (is_a($mail_person_obj, 'MailPerson')) {
				return $mail_person_obj;
			}
		} else if (is_string($mail_person_obj)) {
			return new MailPerson($mail_person_obj);
		}
		return false;
	}
	
	// Setters

	public function addRecipient($mail_person_obj) {
		if ($mail_person = $this->getMailPerson($mail_person_obj)) array_push($this->recipients, $mail_person);
		else return false;
	}
	
	public function resetRecipients() {
		$this->recipients = array();
	}
	
	public function addCcRecipient($mail_person_obj) {
		if ($mail_person = $this->getMailPerson($mail_person_obj)) array_push($this->recipientsCc, $mail_person);
		else return false;
	}
	public function addBccRecipient($mail_person_obj) {
		if ($mail_person = $this->getMailPerson($mail_person_obj)) array_push($this->recipientsBcc, $mail_person);
		else return false;
	}
	
	public function setFrom($mail_person_obj) {
		if ($mail_person = $this->getMailPerson($mail_person_obj)) $this->from = $mail_person;
		else return false;
	}
	public function setReplyTo($mail_person_obj) {
		if (is_object($mail_person_obj) && is_a($mail_person_obj, 'MailPerson')) {
			$this->replyTo = $mail_person_obj;
		}
	}
	
	public function setSubject($subject) { $this->subject = $subject; }
	
	public function addAttachmentByObject($mail_attachment_obj) {
		array_push($this->attachments, $mail_attachment_obj);
	}
	public function addAttachment($attachment_path, $file_name=null, $mime_type=null, $disposition=null) {
		$this->addAttachmentByObject(new MailFileAttachment($attachment_path, $file_name, $mime_type, $disposition));
	}
	
	public function getMailBoundary($boundary_key='default') {
		if (empty($this->mailBoundaries[$boundary_key])) $this->mailBoundaries[$boundary_key] = md5(uniqid(time()));
		return $this->mailBoundaries[$boundary_key];
	}
	
	private function getAttachmentString() {
		$attachments = $this->getAttachments();
		$attachment_string = '';
		
		foreach($attachments as $attachment) {
			$attachment_string .= $attachment->toString( $this->getMailBoundary() ) . "\n";
		}
		return $attachment_string;
	}
	
	private function getBodySectionString() {
		$message_parts = $this->getBodySections();
		$message_body = '';
		foreach($message_parts as $message_part) {
			$message_body .= $message_part->toString( $this->getMailBoundary() ) . "\n\n";
		}
		return $message_body;
	}
	
	public function getPlainTextBody() { return $this->bodyPlain; }
	public function getHtmlBody() { return $this->bodyHtml; }
	
	public function setPlainTextBody($plain_text) { $this->bodyPlain = $plain_text; }
	public function setHtmlBody($html_text) { $this->bodyHtml = $html_text; }
	public function setBody($plain_text, $html='') {
		$this->setPlainTextBody($plain_text);
		$this->setHtmlBody($html);
	}
	
	public function hasHtmlBody() {
		$html = $this->getHtmlBody();
		return (!empty($html));
	}
	
	public function send() {
		$message = '';
		$headers = 'From: ' . $this->getFromString() . "\n" . 'Reply-To: ' . $this->getReplyToString(); 
		
		// Email w/ attachment(s)
		if ($this->hasAttachments()) {
			$headers .= "\nContent-Type: multipart/mixed; boundary=\"".$this->getMailBoundary()."\""; 
			$message .= '--' . $this->getMailBoundary() . "\n";
			
			// HTML email w/ attachment(s)
			if ($this->hasHtmlBody()) {
				$message .= 'Content-Type: multipart/alternative; boundary="PHP-alt-' . $this->getMailBoundary(). '"' . "\n";
			}
			
		// Email with HTML and plain text (but no attachments)
		} else if ($this->hasHtmlBody()) {
			$headers .= "\n" . 'Content-Type: multipart/alternative; boundary="PHP-alt-' . $this->getMailBoundary(). '"';
		
		// Plain text email (no attachments)
		} else {
			$headers .= "\n" . 'Content-Type: text/plain; charset="iso-8859-1" ' . "\n";
			$headers .= 'Content-Transfer-Encoding: 7bit';
		}

		if ($this->hasHtmlBody() || $this->hasAttachments()) {
			if ($this->hasHtmlBody()) {
				$message .= "\n";
				$message .= '--PHP-alt-' . $this->getMailBoundary() . "\n";
			}
			$message .= 'Content-Type: text/plain; charset="iso-8859-1" ' . "\n";
			$message .= 'Content-Transfer-Encoding: 7bit' . "\n";
			$message .= "\n";
		}

		$message .= $this->getPlainTextBody();

		if ($this->hasHtmlBody()) {
			$message .= "\n\n";
			$message .= '--PHP-alt-' . $this->getMailBoundary() . "\n";
			$message .= 'Content-Type: text/html; charset="iso-8859-1" ' . "\n";
			$message .= 'Content-Transfer-Encoding: 7bit' . "\n";
			$message .= "\n";
			$message .= $this->getHtmlBody() . "\n\n";
			$message .= "\n";
			$message .= '--PHP-alt-' . $this->getMailBoundary() . '--' . "\n";
		}
		
		if ($this->hasAttachments()) {
			$message .= "\n\n";
			$message .= $this->getAttachmentString();
			$message .= '--' . $this->getMailBoundary() . '--' . "\n";
		}
		$mail_sent = @mail($this->getRecipientsString(), $this->getSubject(), $message, $headers ); 
		return $mail_sent;
	}
	
}

?>