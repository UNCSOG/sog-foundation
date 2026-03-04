import "./network-admin.scss";
import AdminAjaxButton from "../scripts/class-admin-ajax-buttons";
import domReady from "@wordpress/dom-ready";

domReady(function () {
	//the btn els
	var unPubBtnEl = document.getElementById('unpublish-alert-button');
	var pubBtnEl = document.getElementById('publish-alert-button');
      var statusEl = document.querySelector('.alert-service-data-container .status');

      if( unPubBtnEl && pubBtnEl && statusEl){
            new AdminAjaxButton({
                  el: unPubBtnEl,
                  method: 'POST',
            }).on('data', function (e) {
                  if ((e.status = "success")) {
                        unPubBtnEl.setAttribute('disabled','true');
                        pubBtnEl.removeAttribute('disabled');
                        statusEl.innerText = 'unpublished'
                  } else {
                        console.log(e.msg);
                  }
            });
      
            //the publish button
            new AdminAjaxButton({
                  el: pubBtnEl,
                  method: 'POST',
            }).on('data', function (e) {
                  if ((e.status = "success")) {
                        unPubBtnEl.removeAttribute('disabled');
                        pubBtnEl.setAttribute('disabled','true');
                        statusEl.innerText = 'published'
                  } else {
                        console.log(e.msg);
                  }
            });
      } else {
            console.log('missing something important');
      } 
	
});
