/**
	Kailash Nadh,	http://kailashnadh.name
	September 2011
	Smooth popup dialog for jQuery
	http://kailashnadh.name/code/jqdialog

	License: GPL
	
	v1.5	August 8 2013	-	Fixed flickering when a dialog is already visible
	v1.4	September 21 2011	-	Added support for Enter and Escape keyboard shortcuts.
	v1.3.1	April 4 2011	-	Fixed an IE compatibility issue. Thanks to Filip Vojtisek.
	v1.3	February 6 2011	-	Rewrote the whole plugin to comply with jQuery's plugin standards
	v1.2	September 2 2009
**/

(function($) {
	
	var t = null;
	$.jqDialog = {
		escape_callback: null,
		enter_callback: null,
		active: false,
		close_timer: null,

		//________button / control labels
		labels: {
			ok: 'Ok',
			yes: 'Yes',
			no: 'No',
			cancel: 'Cancel'
		},

		//________element ids
		ids: {
			div_box:	'jqDialog_box',
			div_content:	'jqDialog_content',
			div_options: 'jqDialog_options',
			bt_yes: 'jqDialog_yes',
			bt_no: 'jqDialog_no',
			bt_ok: 'jqDialog_ok',
			bt_ancel: 'jqDialog_ok',
			input: 'jqDialog_input'
		},
		
		//________confirm dialog
		confirm: function(message, callback_yes, callback_no) {
			t.create(message);
			
			t.parts.bt_ok.hide();
			t.parts.bt_cancel.hide();
			
			t.parts.bt_yes.show();
			t.parts.bt_no.show();
			t.parts.bt_yes.focus();
			
			// just redo this everytime in case a new callback is presented
			t.parts.bt_yes.unbind().click( function() {
				t.cleanKeypressCallbacks();
				t.close();
				if(callback_yes) callback_yes();
			});
			// redundant method for 'enter' key binding
			t.enter_callback = function() {
				if(callback_yes) callback_yes();
			};

			t.parts.bt_no.unbind().click( function() {
				t.cleanKeypressCallbacks();
				t.close();
				if(callback_no) callback_no();
			});
			// redundant method for 'escape' key binding
			t.escape_callback = function() {
				if(callback_no) callback_no();
			};
		},
		
		//________prompt dialog
		prompt: function(message, content, callback_ok, callback_cancel) {
			t.create(
				$("<div>").
					append(message)
					.append( $("<div>").append( t.parts.input.val(content) ) )
			);
			
			// activate appropriate controls
			t.parts.bt_yes.hide();
			t.parts.bt_no.hide();

			t.parts.bt_ok.show();
			t.parts.bt_cancel.show(); 
			
			t.parts.input.focus();
			
			// just redo t everytime in case a new callback is presented
			t.parts.bt_ok.unbind().click( function() {
				t.cleanKeypressCallbacks();
				t.close();
				if(callback_ok) callback_ok( t.parts.input.val() );
			});
			t.enter_callback = function() {
				if(callback_ok) callback_ok( t.parts.input.val() );
			};
			
			t.parts.bt_cancel.unbind().click( function() {
				t.cleanKeypressCallbacks();
				t.close();
				if(callback_cancel) callback_cancel();
			});
			t.escape_callback = function() {
				if(callback_cancel) callback_cancel();
			};
		},
		
		//________alert dialog
		alert: function(content, callback_ok) {
			t.create(content);

			// activate appropriate controls
			t.parts.bt_cancel.hide();
			t.parts.bt_yes.hide();
			t.parts.bt_no.hide();
			
			t.parts.bt_ok.show();
			
			t.parts.bt_ok.focus();
			
			// just redo this everytime in case a new callback is presented
			t.parts.bt_ok.unbind().click( function() {
				t.cleanKeypressCallbacks();
				t.close();
				if(callback_ok) {
					callback_ok();
				}
			});
			t.escape_callback = function() {
				if(callback_ok) {
					callback_ok();
				}
			};
		},

		//________content
		content: function(content, close_seconds) {
			t.create(content);
			t.parts.div_options.hide();
		},

		//________auto-hiding notification
		notify: function(content, close_seconds) {
			t.content(content);
			if(close_seconds) {
				t.close_timer = setTimeout(function() { t.close(); }, close_seconds*1000 );
			}
		},

		//________create a dialog box
		create: function(content) {
			$('#background').fadeIn("slow");
			$('body').css('overflow', 'hidden');
			window.clearTimeout(t.close_timer);
			t.check();
			
			t.maintainPosition( t.parts.div_box );
			
			clearTimeout(t.close_timer);
			t.parts.div_content.html(content);
			t.parts.div_options.show();

			if(!t.active) {
				t.parts.div_box.fadeIn('slow');
			} else {
				t.parts.div_box.show();					
			}

			t.active = true;
		},
		//________close the dialog box
		close: function() {
			$('#background').fadeOut("slow");
			$('body').css('overflow', '');
			t.close_timer = window.setTimeout(function() {
				t.parts.div_box.fadeOut('slow');
				t.clearPosition();
				t.active = false;
			});
		},

		//________position control
		clearPosition: function() {
			$(window).unbind('scroll.jqDialog');
		},
		makeCenter: function(object) {
			object.css({
				top: ( (($(window).height() / 2) - ( object.height() / 2 ) )) + ($(document).scrollTop()) + 'px',
				left: ( (($(window).width() / 2) - ( object.width() / 2 ) )) + ($(document).scrollLeft()) + 'px'
			});
		},
		maintainPosition: function(object) {
			t.makeCenter(object);
			
			$(window).bind('scroll.jqDialog', function() {
				t.makeCenter(object);
			} );
		},

		//________
		init_done: false,
		check: function() {
			if(t.init_done)
				return;
			else {
				t.init_done = true;
			}
			
			$('body').append( t.parts.div_box );
		},
		init: function() {
			t.parts = {};
			
			// create the dialog components
			t.parts.div_box = $("<div>").attr({ id: t.ids.div_box });
			t.parts.div_content = $("<div>").attr({ id: t.ids.div_content });
			t.parts.div_options = $("<div>").attr({ id: t.ids.div_options });

			t.parts.bt_yes = $("<button>").attr({ id: t.ids.bt_yes }).append( t.labels.yes );
			t.parts.bt_no = $("<button>").attr({ id: t.ids.bt_no }).append( t.labels.no );
			t.parts.bt_ok = $("<button>").attr({ id: t.ids.bt_ok }).append( t.labels.ok );
			t.parts.bt_cancel = $("<button>").attr({ id: t.ids.bt_cancel }).append( t.labels.cancel );

			t.parts.input = $("<input>").attr({ id: t.ids.input });

			// assemble the parts
			t.parts.div_box.append( t.parts.div_content )
					.append(
						t.parts.div_options.append(t.parts.bt_yes)
										   .append(t.parts.bt_no)
										   .append(t.parts.bt_ok)
										   .append(t.parts.bt_cancel)
					);

			// add to body
			t.parts.div_box.hide();
			
			// keyboard bindings
			$(document).keyup(function(e) {
				if(e.altKey) return;

				if (e.keyCode == 13) {
					t.enterPressed();
				}
				if (e.keyCode == 27) {
					t.escapePressed();
				}
			});
		},
		cleanKeypressCallbacks: function() {
			t.enter_callback = null;
			t.escape_callback = null;
		},
		escapePressed: function() {
			t.close();
			if(t.escape_callback) {
				t.enter_callback = null;
				t.escape_callback();
				t.escape_callback = null;
			}
		},
		enterPressed: function() {
			t.close();
			if(t.enter_callback) {
				t.escape_callback = null;
				t.enter_callback();
				t.enter_callback = null;
			}
		},
	};
	t = $.jqDialog;
	$.jqDialog.init();
})(jQuery);
