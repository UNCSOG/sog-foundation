
/**
 * a simple event dispatcher
 * https://medium.com/@muhammad_hamada/simple-event-dispatcher-implementation-using-javascript-36d0eadf5a11
 */


export default class eventDispatcher{

    constructor(){
        this.events = {}; 
    }

    /**
     * add the listener
     * @param {*} event 
     * @param {*} callback 
     * @returns 
     */
     on(event, callback) {
        // Check if the callback is not a function
        if (typeof callback !== 'function') {
            console.error(`The listener callback must be a function, the given type is ${typeof callback}`);
            return false;
        }


        // Check if the event is not a string
        if (typeof event !== 'string') {
            console.error(`The event name must be a string, the given type is ${typeof event}`);
            return false;
        }

        // Check if this event does not exist
        //if not, add it
        if (this.events[event] === undefined) {
            this.events[event] = {
                listeners: []
            }
        }
        //console.log( this.events[event] );
        this.events[event].listeners.push(callback);
    }

    /**
     * Remove the listener
     * 
     * @param {String} event 
     * @param {Function } callback 
     * @returns {String or bool} The listener or false
     */
    off(event, callback) {
        // Check if this event not exists
        if (this.events[event] === undefined) {
            console.error(`This event: ${event} does not exist`);
            return false;
        }

        this.events[event].listeners = this.events[event].listeners.filter(listener => {
            return listener.toString() !== callback.toString();
        });
    }

    /**
     * Dispatch the event
     * @param {String} event 
     * @param {Anything} details 
     * @returns 
     */
    dispatch(event, details) {
        // Check if this event does not exist
        if (this.events[event] === undefined) {
            console.log(`This event: ${event} does not exist, no one has called it`);
            return false;
        }

        this.events[event].listeners.forEach((listener) => {
            listener(details);
        });
    }
}