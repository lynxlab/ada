(function (window) {
    const debug = false;
    const VIDEOPLAYERID = 'jquery_jplayer';
    const TIMELEFTID = 'node-time-left';

    class timedNodeManager {

        constructor(options) {
            if (debug) {
                // options.duration = 5;
            }
            const firstVideo = Array.from(document.querySelectorAll(`*[id^="${VIDEOPLAYERID}"]`)).reverse().pop();
            this.interval = null;
            this.options = extend(true, timedNodeManager.defaults, options);
            this.nextBtn = window.document.getElementById('nextNodeBtn');
            this.timeLeftEl = window.document.getElementById(TIMELEFTID);
            this.timeLeft = this.options.duration;
            this.videoElement = window.document.getElementById(firstVideo?.id ?? null);
            this.ended = {
                video: (null === this.videoElement), // if no videoElement, video has ended
                time: false,
            };
            this.startManage(this.options.duration);
        }

        startManage(duration) {
            if (duration > 0 && this.nextBtn) {
                if (this.buttonEnabled(this.nextBtn)) {
                    this.toggleButton(this.nextBtn);
                }
                if (null !== this.timeLeftEl) {
                    this.timeLeftEl.innerHTML = this.formatHMS(duration);
                }
                this.nextBtn.addEventListener('click', (e) => this.clickHanlder(this.nextBtn, e));
                const saveData = {
                    userId: this.options.userId,
                    instanceId: this.options.instanceId,
                };
                if (null !== this.videoElement && !this.ended.video) {
                    // must use jQuery here, the jPlayer is jQuery stuff!
                    $j(`#${this.videoElement.id}`).bind(`${$j.jPlayer.event.ended}.jp-timednode`, (event) => {
                        // Using ".jp-timednode" namespace so we can easily remove this event
                        this.doneVideo(saveData);
                      });
                }
                this.setIntervalCallback(() => {
                    this.timeLeft--;
                    if (debug) {
                        console.log(`timednode: ${this.timeLeft} seconds left, ended: ${JSON.stringify(this.ended)}`);
                    }
                    if (null !== this.timeLeftEl && 0 === this.timeLeft % 60) {
                        this.timeLeftEl.innerHTML = this.formatHMS(this.timeLeft);
                    }
                    if (this.timeLeft == 0) {
                        this.doneTimer(saveData);
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
                if (null !== this.timeLeftEl.parentElement?.parentElement) {
                    this.timeLeftEl.parentElement.parentElement.addEventListener("animationend", () => {
                        this.timeLeftEl.parentElement.parentElement.style.display = "none";
                        this.timeLeftEl.parentElement.parentElement.remove();
                    });
                    this.timeLeftEl.parentElement.parentElement.classList.add('fadeOut');
                }
                button.style.pointerEvents = "auto";
                button.classList.remove('disabled');
            }
        }

        clickHanlder(button, event) {
            if (!this.buttonEnabled()) {
                event.preventDefault();
                return;
            }
        }

        doneVideo(saveData) {
            if (debug) {
                console.log('doneVideo');
            }
            if (null !== this.videoElement) {
                $j(`#${this.videoElement.id}`).unbind(`${$j.jPlayer.event.ended}.jp-timednode`);
            }
            this.ended.video = true;
            this.checkAllEnded(saveData);
        }

        doneTimer(saveData) {
            if (debug) {
                console.log('doneTimer');
            }
            this.clearInterval();
            this.ended.time = true;
            this.checkAllEnded(saveData);
        };

        checkAllEnded(saveData) {
            if (debug) {
                console.log(`checkAllEnded: ${JSON.stringify(this.ended)}`);
            }
            if (Object.values(this.ended).every(item => item === true)) {
                this.allEnded(saveData);
            }
        };

        allEnded(saveData) {
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

        formatHMS(timestamp) {
            return new Date(timestamp * 1000).toISOString().substring(11, 19);
        };
    }

    timedNodeManager.defaults = {
        url: MODULES_TIMEDNODE_HTTP,
    };
    window.timedNodeManager = timedNodeManager;
})(this);
