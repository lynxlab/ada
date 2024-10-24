(function (window) {
    const debug = false;

    class timedNodeManager {

        constructor(options) {
            if (debug) {
                options.duration = 5;
            }
            this.interval = null;
            this.options = $j.extend({}, timedNodeManager.defaults, options);
            this.nextBtn = window.document.getElementById('nextNodeBtn');
            this.timeLeft = this.options.duration;
            this.startManage(this.options.duration);
        }

        startManage(duration) {
            if (duration > 0 && this.nextBtn) {
                this.toggleButton(this.nextBtn);
                this.nextBtn.addEventListener('click', (e) => this.clickHanlder(this.nextBtn, e));
                this.setIntervalCallback(() => {
                    if (--this.timeLeft == 0) {
                        this.doneTimer({
                            userId: this.options.userId,
                            instanceId: this.options.instanceId,
                        });
                    }
                });
            }
        }

        setIntervalCallback(fn) {
            if (null === this.interval) {
                this.interval = window.setInterval(() => {
                    fn();
                }, 1000);
            }
        }

        clearInterval() {
            if (null !== this.interval) {
                window.clearInterval(this.interval);
                this.interval = null;
            }
        }

        buttonEnabled(button) {
            return !button.classList.contains('disabled');
        }

        toggleButton(button) {
            if (this.buttonEnabled(button)) {
                button.style.pointerEvents = "none";
                button.classList.add('disabled');
            } else {
                button.style.pointerEvents = "auto";
                button.classList.remove('disabled');
            }
        }

        clickHanlder(button, event) {
            if (button.classList.contains('disabled')) {
                event.preventDefault();
                return;
            }
        }

        doneTimer(saveData) {
            this.clearInterval();
            const url = new URL(this.nextBtn.getAttribute('href'));
            saveData.nextNode = url.searchParams.get('id_node');

            if (debug) {
                console.log(`calling url: ${this.options.url}/ajax/nodeTimerExp.php`);
                console.log(saveData);
            }

            fetch(`${this.options.url}/ajax/nodeTimerExpired.php`, {
                method: "post",
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saveData)
            })
            .then(response => {
                //Here body is not ready yet, throw promise
                if (!response.ok) throw response;
                return response.json();
            })
            .then(json => {
                if (debug) {
                    console.log('got response:');
                    console.log(json);
                }
                if (json.status == 'OK') {
                    this.toggleButton(this.nextBtn);
                } else {
                    if (debug) {
                        console.log('json status was not OK');
                    }
                }
            })
            .catch(async response => {
                if (debug) {
                    const body = await response.text();
                    console.log('got error');
                    console.log(body);
                }
            });
        };
    }

    timedNodeManager.defaults = {
        url: MODULES_TIMEDNODE_HTTP,
    };
    window.timedNodeManager = timedNodeManager;
})(this);
