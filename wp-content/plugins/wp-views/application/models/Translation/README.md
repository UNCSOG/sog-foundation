# The "Translation" directory.


* **Translation**<br>
Models of Translation.

	* **Frontend**<br>
		The Frontend directory holds the translations apply for the frontend display.
		
   		1. **Common**<br>
       	Shared logic for View and WPA. 
		1. **View**<br>
			Everything related to a View.
		1. **WPA**<br>
			Everything related to a Wordpress Archive.

	* **RegisterAndStore**<br>
		The RegisterAndStore directory holds all the logic needed to find extraordinary
		strings inside the View / WPA to register them in the translation package and 
		to store the translated strings (which are comming from ATE / CTE).

		* **Block**<br>
			String registration from Blocks to WPML and once the translation is completedet 
			the translated strings are applied to the post of the target language.
		
		* **Common**<br>
			Shared logic for Blocks and Shortcode.
			
		* **Shortcode**<br>
			99% of all shortcode attributes can be registered by the wpml-config.xml. But
			we have a special attribute "display_values", which stores different user entries
			as a comma separated value. This list will be splitted to have one translation
			entry per user entry.
			

**Attention:** <br>
This part here fullfill the translation of Views/WPA. The most part translatable parts are
defined in the wpml-config.xml. (And there's probably also some stuff in older parts of
Views).

# Structure of each Model

Each Model has these directories:

* **Application**<br>
  Here are the entry points for the Domain located, called Services. The only way to use 
  the Domain is through Services (except for the Common). Services does not contain any 
  business logic, which makes them usally very very thin. One task per Service is recommended.

* **Domain**<br>
  The complete business logic goes into this directoy. Everything inside this is 
  independent from the outside (except the Common part). It's a self-working unit. The 
  domain does not care how it's persisted.

  Feel free to separate the Domain with subfolders.

* **Infrastructure**<br>
Everything related to the system is stored here:

	* **EventListener**<br>
	Place of events (actions/filters) to trigger Domain actions *through* Services. One 
	event listener per file is recommended. Name the file according the used action/filter.

	* **Repository**<br>
	Storage of the Domain. In our case WordPress with SQL will probably be the one and 
	only Storage forever.