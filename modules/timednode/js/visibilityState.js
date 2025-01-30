(function (window) {
    const debug = false;

    class visibilityState {

        static events = {
            onstatechange: 'onstatechange',
        };

        static states = {
            active: 'active',
            passive: 'passive',
            hidden: 'hidden',
            frozen: 'frozen',
            terminated: 'terminated',
        };

        /**
         * Code inspired by:
         * https://developer.chrome.com/docs/web-platform/page-lifecycle-api?hl=it
         */
        constructor() {

            // bind causes a fixed `this` context to be assigned to methods.
            this.logStateChange = this.logStateChange.bind(this);
            this.addEventListeners = this.addEventListeners.bind(this);

            // Init window event listeners
            this.addEventListeners();

            // Stores the initial state using the `getState()`.
            this.state = this.getState();
        }

        addEventListeners = () => {
            // Options used for all event listeners.
            const opts = { capture: true };
            // These lifecycle events can all use the same listener to observe state
            // changes (they call the `getState()` function to determine the next state).
            ['pageshow', 'focus', 'blur', 'visibilitychange', 'resume'].forEach((type) => {
                window.addEventListener(type, () => this.logStateChange(this.getState()), opts);
            });

            // The next two listeners, on the other hand, can determine the next
            // state from the event itself.
            window.addEventListener('freeze', () => {
                // In the freeze event, the next state is always frozen.
                this.logStateChange(visibilityState.states.frozen);
            }, opts);

            window.addEventListener('pagehide', (event) => {
                // If the event's persisted property is `true` the page is about
                // to enter the back/forward cache, which is also in the frozen state.
                // If the event's persisted property is not `true` the page is
                // about to be unloaded.
                this.logStateChange(
                    event.persisted ? visibilityState.states.frozen : visibilityState.states.terminated
                );
            }, opts);
        };

        getState = () => {
            if (document.visibilityState === 'hidden') {
                return visibilityState.states.hidden;
            }
            if (document.hasFocus()) {
                return visibilityState.states.active;
            }
            return visibilityState.states.passive;
        };

        // Accepts a next state and, if there's been a state change, logs the
        // change to the console. It also updates the `state` value defined above.
        logStateChange = (nextState) => {
            const prevState = this.state;
            if (nextState !== prevState) {
                if (debug) {
                    console.log(`State change: ${prevState} >>> ${nextState}`);
                }
                this.state = nextState;
                this.trigger(
                    visibilityState.events.onstatechange,
                    { prevState: prevState, state: this.state }
                );
            }
        };

        /**
         *
         * Add an event listener.
         *
         * @param {string|number} e
         * @param {function} listener
         */
        on = (e, listener) => {
            this._events = this._events || {};
            this._events[e] = this._events[e] || [];
            this._events[e].push(listener);
        };

        /**
         * Remove an event listener.
         *
         * @param {string|number} e
         * @param {function} listener
         */
        removeListener = (e, listener) => {
            this._events = this._events || {};
            if (e in this._events) {
                this._events[e].splice(this._events[e].indexOf(listener), 1);
            }
        };

        /**
         * Call each listener bound to a given event with the supplied arguments.
         * Not an arrow function because it needs arguments.
         * See https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Errors/Arguments_not_allowed
         *
         * @param {string|number} e
         * @param {object} args Array of arguments to apply to the listeners.
         * @api private
         */
        trigger = function (e, args) {
            this._events = this._events || {};
            if (e in this._events === false) {
                return;
            }
            for (var i = this._events[e].length; i--;) {
                this._events[e][i].apply(this, Array.prototype.slice.call(arguments, 1));
            }
        };
    }

    window.visibilityState = visibilityState;
})(this);
