import "./alert-banner-frontend.scss";

window.addEventListener("load", (event) => {
	//console.log("front");
	var $alertMsg = document.querySelector("#alert-banner-rest-msg");

	if ($alertMsg) {
		$alertMsg.parentNode.parentNode.style.display = "none";
		console.log("hidden");

		var options = {
			method: "GET",
			headers: {
				Accept: "application/json",
				"Content-Type": "application/json",
			},
		};

		console.log(alertBannerFrontendScriptVars.restUrl);

		fetch(alertBannerFrontendScriptVars.restUrl + "cap/json/", options)
			.then((response) => {
				if (!response.ok) {
					throw new Error("Server returned " + response.status);
				}
				return response.json();
			})
			.then((data) => {
				//console.log(data.cap.info)
				if (data["publish-status"] == "published" && data.cap.info.headline && data.cap.info.parameter.value != "Normal") {
					$alertMsg.innerHTML = data.cap.info.headline;
					$alertMsg.parentElement.parentElement.style.display = "block";
					//console.log('shown');
				} else {
					$alertMsg.innerHTML = "no cap message found";
				}
			})
			.catch((error) => {
				console.error("There was a problem with the Fetch operation:", error);
				console.log("falling back to source");
				//you can test with "https://www.getrave.com/cap/unctest/channel4"
				fetch("https://content.getrave.com/cap/UNC/channel1")
					.then((response) => {
						if (!response.ok) {
							throw new Error("Server returned " + response.status);
						}
						return response.text();
					})
					.then((xmlString) => {
						//console.log(xmlString);
						const parser = new XMLParser();
						let jsonString = parser.parse(xmlString);
						//console.log( jsonString.alert );
						return jsonString.alert;
					})
					.then((data) => {
						//console.log(data);
						if (data.info.headline && data.info.parameter.value != "Normal") {
							$alertMsg.innerHTML = data.info.headline;
							$alertMsg.parentElement.parentElement.style.display = "block";
							console.log("shown");
						}
					});
			});
	}
});
