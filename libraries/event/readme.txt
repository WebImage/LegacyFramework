EVENTS - HOW TO

== STEP #1 ==

1. Load the event manager library: FrameworkManager::loadLibrary('event.manager');

== STEP #2 == 

1. Setup an event handler.  This can be a function, a static class method, or an instance class method.  

2. Register the event handler according to its type

a. Function handler

CWI_EVENT_Manager::listenFor((string)'EventClassName', (string)'camelCaseEventName', (string)'function_name');

b. Static class handler

CWI_EVENT_Manager::listenFor((string)'EventClassName', (string)'camelCaseEventName', array((string)'StaticClassName', (string)'methodName'));

c. Class instance handler

CWI_EVENT_Manager::listenFor((string)'EventClassName', (string)'camelCaseEventName', array((obj)$instanceVar, (string)'handleMethodName'));

d. General event - this is a variation on the above (a, b, c), but without listening to specific objects

CWI_EVENT_Manager::listenForGeneral(string 'camelCaseEventName', [see 2a-c above]);

-------------------------------------------------------
Loosely referenced:
- http://stackoverflow.com/questions/724085/events-naming-convention-and-style
- http://www.codeproject.com/KB/cs/event_fundamentals.aspx

Event naming conventions:
- Use camelCase
- Use names that end with a verbe followed by -ing or -ed (Closing/Closed, Loading/Loaded)

-------------------------------------------------------

Handler naming conventions:
- Function should be in the form: "handle_[event_name]"
- Classes should be in the form: "[ClassName]Handler::on[EventName] - e.g. MyPageHandler::onPageRendering($event, $args)  -- or --  MyPageHandler::onPageRendered($event, $args)

-------------------------------------------------------

Example:

class TestingEvent {
	function doIt() {
		$test = new CWI_EVENT_Args();
		$test->name = '';
		echo '<p>Test Name: ' . $test->name . '</p>';
	}
}

class TestingHandler {
	function handleIt($event, $data) {
		// $event->getSender()
		$data->name = $data->name . '(TestingHandler::handleIt)';
	}
}
class TestingHandler2 {
	var $val = 'Something Else';
	function handleIt($event, $data) {
		$data->name = $data->name . '(TestingHandler2::handleIt)';
	}
}

$instance = new TestingHandler2();

CWI_EVENT_Manager::listenFor('TestingEvent', 'onDoIt', 'doIt'); // Function handler
CWI_EVENT_Manager::listenFor('TestingEvent', 'onDoIt', array('TestingHandler', 'handleIt')); // Static Class Handler
CWI_EVENT_Manager::listenFor('TestingEvent', 'onDoIt', array($instance, 'handleIt'), 1); // Class Instance Handler


$test = new TestingEvent();
$test->doIt();