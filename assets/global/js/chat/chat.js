var defaultOptions = {
	connectionUrl: '',
	pollUpdatesInterval: 2000	
}
function ChatSession(chat_options) {
	this.sessionEstablished = false; // Whether or not any a session has been established
	this.sessionId = null;
	this.busy = false;
	this.numErrors = 0;
	this.errorThreshold = 3;
	//this.waitingSession;
	this.options = defaultOptions;
	this.updateTimer = null;
	jQuery.extend(this.options, chat_options);
	
	if (this.options.connectionUrl.length == 0) {
		alert('Missing required setting: connectionUrl');
		return false;
	}
	
	this.init();
}

ChatSession.prototype.init = function() {
	delegate = this;
	$(this).bind('onActionMessage', function(ev, data) { delegate.onActionMessage(ev, data); });
}
ChatSession.prototype.resetErrors = function() { this.numErrors = 0; }
ChatSession.prototype.checkErrorThreshold = function() {
	if (this.numErrors > this.errorThreshold) {
		//alert('Your chat session has exceeded the maximum number of errors.  Please contact support.');
		/*
				if (errorMessageCount < allowedErrorMessages) {
					alert('There was a problem retrieving any updates.  We will try ' + (allowedErrorMessages-errorMessageCount) + ' more times before stopping');
				} else if (errorMessageCount == allowedErrorMessages) {
					alert('This was the final attempt at retrieving message updates.  We will stop trying now.  Please contact support.');
					continueUpdating = false;
				}
		*/
		delegate.setSessionEstablished(false);
	}
}
ChatSession.prototype.requestNewSession = function() {
	this.setBusy(true);
	delegate = this; // for delegate
	this.sendAction(
		'NewSession', 
		{}, 
		function(json) {
			delegate.handleMessageReceipt(json);
			delegate.resetPollUpdateLoop();
			delegate.resetErrors();
		},
		function() {
			delegate.numErrors ++;
			delegate.checkErrorThreshold();
		}
		);
	/*
	jQuery.ajax({
		dataType: 'json',
		url: chat_options.connectionUrl + '?action=newsession',
		error:function() {
			delegate.numErrors ++;
			delegate.checkErrorThreshold();
		},
		success: function(json) {
			delegate.handleMessageReceipt(json);
			delegate.resetPollUpdateLoop();
			delegate.resetErrors();
		}
	});
	*/
}
ChatSession.prototype.requestExistingSession = function(chat_session) {
	this.setBusy(true);
	delegate = this; // for delegate
	/*
	jQuery.ajax({
		    type:'POST',
		    url:chat_options.connectionUrl + '?action=joinsession&chatsession=' + chat_session,
		    dataType:'json',
		    error:function() { alert('Failed'); },
		    success: function(json) {
			    delegate.handleMessageReceipt(json);
			    delegate.resetPollUpdateLoop();
		    }
	});
	*/
}
// Getters
ChatSession.prototype.isSessionEstablished = function() { return this.sessionEstablished; }
ChatSession.prototype.isBusy = function() { return this.busy; }
ChatSession.prototype.getSessionId = function() { return this.sessionId; }
// Setters
ChatSession.prototype.setSessionEstablished = function(true_false) { this.sessionEstablished = true_false; }
ChatSession.prototype.setBusy = function(true_false) { this.busy = true_false; }
ChatSession.prototype.setSessionId = function(session_id) {
	this.sessionId = session_id;
}
// Event handler

ChatSession.prototype.onActionMessage = function(ev, data) {
	if (data.action) {
		switch (data.action) {
			case 'ActivateSession':
				if (data.sessionId) {
					this.setSessionEstablished(true);
					this.setSessionId(data.sessionId);
				}
				break;
			case 'CloseSession':
				this.setSessionEstablished(false);
				this.setSessionId(null);
				break;
			case 'ChangeConnectionUrl':
				if (data.url) {
					this.options.connectionUrl = data.url;
				}
		}
		// Trigger an event for the actual action
		var action_event_type = 'on' + data.action;
		$(this).trigger(action_event_type, data);
	}
}
ChatSession.prototype.handleMessageReceipt = function(data) {
	if (data.response) {
		//$(this).trigger('onHandleMessageReceipt', {});
		if (data.response.status) {
			if (data.response.status.toLowerCase() != 'success') return false;
			
			if (data.response.communications) {
				if (data.response.communications.length > 0) {
					communications = data.response.communications;
					
					for (i=0; i < communications.length; i++) {
						communication = communications[i];
						if (communication.type) {
							var event_type = 'on' + communication.type + 'Message';
							$(this).trigger(event_type, communication);
						}
					}
				}
				
			}
		} else return false;
	}
	this.setBusy(false);
	//alert('Handle message receipt');
}

/**
 * @param string action
 * @param object parameters
 */
ChatSession.prototype.sendAction = function(action, parameters, success_func, error_error) {
	if (typeof(parameters) == 'undefined') parameters = {};
	parameters.action = action;

	delegate = this;
	jQuery.ajax({
		    dataType:"json",
		    data:parameters,
		    error:error_error,
		    success:success_func,
		    type:"POST",
		    url:delegate.options.connectionUrl
		    });
}

ChatSession.prototype.sendMessage = function(message) {
	//if (this.isSessionEstablished() && !this.isBusy()) {
	if (this.isSessionEstablished()) {
		//this.setBusy(true);
		this.sendAction('NewMessage', {message:message, chatsession:this.getSessionId()});
		/*
		$.post(this.options.connectionUrl, {
		       action:"newmessage",
		       message:message,
		       chatsession:this.getSessionId()
			
		});
		*/
//		$.post();
	}
}

ChatSession.prototype.resetPollUpdateLoop = function() {
	delegate = this; // for delegate function
	
	this.updateTimer = setTimeout(function() { delegate.pollUpdates(); }, this.options.pollUpdatesInterval);
}
ChatSession.prototype.pollUpdates = function() {
	delegate = this; // for delegate function
	
	if (this.isSessionEstablished() && !this.isBusy()) {
		this.setBusy(true);
		delegate = this;
		delegate.sendAction(
			'GetUpdates',
			{chatsession:delegate.getSessionId()}, 
			function(json) {
				delegate.resetErrors();
				delegate.handleMessageReceipt(json);
			},
			function() { 
				delegate.numErrors ++;
				delegate.checkErrorThreshold();
			});
		/*
		$.ajax({
			dataType: "json",
			url: delegate.options.connectionUrl + "?action=getupdates&chatsession=" + this.sessionId,
			error: function() { 
				delegate.numErrors ++;
				delegate.checkErrorThreshold();
			},
			success: function(json) {
				delegate.resetErrors();
				delegate.handleMessageReceipt(json);
			}
		});
		*/
	}
	this.resetPollUpdateLoop();
}
ChatSession.createNewSession = function(chat_options) {
	var session = new ChatSession(chat_options);
	session.requestNewSession();
	return session;
}
ChatSession.joinExistingSession = function(session_id, chat_options) {
	var session = new ChatSession(chat_options);
	session.requestExistingSession(session_id);
	return session;
}