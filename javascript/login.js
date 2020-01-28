// This file is part of Moodle - http://moodle.org/
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

$(function(){
	var $form = $('form#login2');
	if (!$form.length) {
		// try to find other form
		$form = $(':password:first').closest('form');
	}

	$form.attr('action', M.cfg.wwwroot+'/blocks/exa2fa/login/');

	var strA2faPassword = $('html').attr('lang') == 'de' ? 'Authenticator Einmalpasswort' : 'A2fa Code';
	var strForgotPassword = $('html').attr('lang') == 'de' ? 'A2fa Code vergessen?' : 'Forgot your A2fa Code?';

	// add a2fa to form
	var $innerWrap = $form.wrapInner('<div/>').children();
	var $tokenForm = $(
		'<div id="a2fa-token-form">' +
		'	<div class="clearer"><!-- --></div>' +
		'	<div class="form-label"><label for="token">'+strA2faPassword+'</label></div>' +
		'	<div class="form-input">' +
		'		<input type="text" name="token" id="token" size="15" value="" class="form-control"/>' +
		'	</div>' +
		'</div>'
	).hide().appendTo($form);

	$('<div><a href="#">'+strForgotPassword+'</a></div>')
		.appendTo($tokenForm)
		.find('a').click(function(){
			$form.attr('action', M.cfg.wwwroot+'/blocks/exa2fa/send_a2fa.php');
			// submit the form, without triggering the special login handler
			$form[0].submit();
		});



	// add submit button also to token form
	$form.find(':submit').clone().appendTo($tokenForm);

	function error(errorText) {
		$('.loginerrors').remove();
		$form.before('<div class="loginerrors"><div class="alert alert-danger" role="alert"><span class="error">'+errorText+'</span></div></div>');
	}
	
	$form.submit(function(event){
		// Stop form from submitting normally
		event.preventDefault();

		// remove old errors
		$('.loginerrors').remove();

		var data = {
			ajax: true,
		};
		$.each($form.serializeArray(), function(_, input) {
			data[input.name] = input.value;
		});

		// disable form after serializeArray(), else this won't work
		$('input').attr('disabled', 'disabled');

		// Send the data using post
		$.ajax({
			dataType: "json",
			url: $form.attr('action'),
			dataType: 'json',
			method: 'post',
			data: data
		}).done(function(data, ret, xhr){
			// for testing
			/*
			window.content = content;
			window.xhr = xhr;
			window.ret = ret;
			return;
			*/

			if (data['a2fa-error']) {
				error(data['a2fa-error']);

				// a2fa error
				$innerWrap.hide();
				$tokenForm.show();
				$('input').attr('disabled', null);
				$('input[name=token]').val('').focus();
			} else if (data.url) {
				// we got an url -> redirect
				window.setTimeout(function(){
					document.location.href = data.url;
				}, 1);
			} else {
				// could check data.error here, but not needed, because moodle shows error after reload
				location.reload(true);
			}
		}).fail(function(){
			error('Unknown Error');
		});
	});
	
	// show error on page load, if non present
	if (!$('.loginerrors').length && document.location.href.match(/[&?]errorcode=4(&|$)/)) {
		error(M.util.get_string('sessionerroruser', 'error'));
	}
});
