/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * @brief this object takes care of the filter funcationality on the user
 * management page
 * @param jQuery input element that works as the user text input field
 * @param object the UserList object
 */
function UserManagementFilter(filterInput, userList) {
	this.filterInput = filterInput;
	this.userList = userList;
	this.thread = undefined;
	this.oldval = this.filterInput.val();

	this.init();
}

/**
 * @brief sets up when the filter action shall be triggered
 */
UserManagementFilter.prototype.init = function() {
	umf = this;
	this.filterInput.keyup(function(e) {
		console.log(e.keyCode);

		//we want to react on any printable letter, plus on modyfing stuff like
		//Backspace and Delete. extended https://stackoverflow.com/a/12467610
		var valid =
			e.keyCode ===  0 || e.keyCode ===  8  || // like ö or ж; backspace
			e.keyCode ===  9 || e.keyCode === 46  || // tab; delete
			e.keyCode === 32                      || // space
			(e.keyCode >  47 && e.keyCode <   58) || // number keys
			(e.keyCode >  64 && e.keyCode <   91) || // letter keys
			(e.keyCode >  95 && e.keyCode <  112) || // numpad keys
			(e.keyCode > 185 && e.keyCode <  193) || // ;=,-./` (in order)
			(e.keyCode > 218 && e.keyCode <  223);   // [\]' (in order)

		//besides the keys, the value must have been changed compared to last
		//time
		if(valid && umf.oldVal !== umf.getPattern()) {
			clearTimeout(umf.thread);
			umf.thread = setTimeout(
				function() {
					umf.run();
				},
				300
			);
		}
		umf.oldVal = umf.getPattern();
	});
}

/**
 * @brief the filter action needs to be done, here the accurate steps are being
 * taken care of
 */
UserManagementFilter.prototype.run = function() {
	this.userList.empty();
	this.userList.update();
}

/**
 * @brief returns the filter String
 * @returns string
 */
UserManagementFilter.prototype.getPattern = function() {
	return this.filterInput.val();
}