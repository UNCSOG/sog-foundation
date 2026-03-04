/**
 * a simple class to make a button query with wp.apiFetch
 */

import apiFetch from "@wordpress/api-fetch";
import EventDispatcher from "./class-event-dspatcher";

export default class ApiFetchButton {
	/**
	 * the dispatcher object
	 */
	dispatcher = null;

	/**
	 * the button element
	 * should be something a user can click
	 */
	el = null;

	/**
	 * the api request url
	 */
	url = null;

	/**
	 * method to use in the api request
	 */
	method = "GET";

	template_name = "alert-service-admin-modal";

	/**
	 *
	 * @param {object} options the options used for building the object
     * @param {Element} options.el the dom element
     * @param {string} options.method GET or POST
     * 
	 */
	constructor(options) {
		//bail if there is no element
		if (options.el == null) {
			console.log("no el provided");
			return false;
		} else {
			//console.log(options.el)
		}

		//set the fetch method
		if (options.method) {
			this.method = options.method;
		}

		//setup a dispatcher
		this.dispatcher = new EventDispatcher();

		//set the element
		this.el = options.el;

		//set the url to use in the fetch
		this.url = this.el.getAttribute("href");

		//listen for a click
		this.el.addEventListener(
			"click",
			function (event) {
				event.preventDefault();
				this.fetch();
			}.bind(this)
		);
	}

	fetch() {
		apiFetch({
			url: this.url,
			method: this.method,
		}).then((res) => {
			//deal with errors here
			if (this.dispatcher !== null) {
				this.dispatcher.dispatch("data", res);
			}
		});
	}

	/**
	 * Pass the on method onto the dispatcher
	 * @param {*} event
	 * @param {*} callback
	 */
	on(event, callback) {
		if (this.dispatcher !== null) {
			this.dispatcher.on(event, callback);
		}
	}

	/**
	 * Pass the off method onto the dispatcher
	 * @param {*} event
	 * @param {*} callback
	 */
	off(event, callback) {
		if (this.dispatcher !== null) {
			this.dispatcher.off(event, callback);
		}
	}
}
