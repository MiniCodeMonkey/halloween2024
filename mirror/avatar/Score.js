import Timeout from 'await-timeout';

class Score {
  constructor(host) {
    this.host = host;
  }

  showCharacter() {
    document.getElementById('renderCanvas').classList.remove('invisible');
    document.getElementById('renderCanvas').classList.remove('animate__fadeOut');
    document.getElementById('renderCanvas').classList.add('animate__animated');
    document.getElementById('renderCanvas').classList.add('animate__slow');
    document.getElementById('renderCanvas').classList.add('animate__fadeIn');
  }

  async hideCharacter() {
    document.getElementById('renderCanvas').classList.add('animate__animated');
    document.getElementById('renderCanvas').classList.add('animate__slow');
    document.getElementById('renderCanvas').classList.add('animate__fadeOut');

    await Timeout.set(2500);
  }

  /**
   * Valid animation names: generic_a, generic_b, generic_c, in, many, one, self, wave, you
   **/
  animate(animation) {
    const defaultGestureOptions = {
      holdTime: 1.5, // how long the gesture should last
      minimumInterval: 0, // how soon another gesture can be triggered
    };

    this.host.GestureFeature.playGesture('Gesture', animation, defaultGestureOptions);
  }

  say(content) {
    this.isTalking = true;
    this.host.TextToSpeechFeature.play(`<speak><amazon:auto-breaths>${content}</amazon:auto-breaths></speak>`);
  }

  whisper(content) {
    this.say(`<amazon:effect name="whispered">${content}</amazon:effect>`);
  }

  buildGestureMark(name, time = 0.8) {
    return `<mark name='{"feature":"GestureFeature","method":"playGesture","args":["Gesture", "${name}", { "holdTime": ${time}, "minimumInterval": 0 }]}'/> `
  }

  async waitUntilDoneTalking() {
    while (this.isTalking) {
      await Timeout.set(100);
    }
  }

  onStopSpeech() {
    this.isTalking = false;
  }

  async scene1() {
    this.showCharacter();

    this.whisper(`
      ${this.buildGestureMark('generic_c')}
      Jeg tror ikk det er en go' idé at går dérind.
    `);
    await this.waitUntilDoneTalking();

    await Timeout.set(2000);
    await this.hideCharacter();
  }

  async scene2() {
    this.ghostSound.play();
    await Timeout.set(12000);

    this.showCharacter();

    this.whisper(`
      ${this.buildGestureMark('many')}
      Hørte du det?
    `);

    await this.waitUntilDoneTalking();

    await Timeout.set(2000);
    await this.hideCharacter();
  }

  async scene3() {
    this.showCharacter();

    this.whisper(`
      ${this.buildGestureMark('you')}
      Hey! Dig dér. Hvad laver du her?

      <break />

      Ved du ikke at det spøger i skoven?
    `);
    await this.waitUntilDoneTalking();

    await this.hideCharacter();
  }

  async scene4() {
    this.showCharacter();

    this.whisper(`
      ${this.buildGestureMark('you')}
      Jeg ville ikke gå ind hvis jeg var dig.
    `);
    await this.waitUntilDoneTalking();

    await Timeout.set(2000);
    await this.hideCharacter();
  }

  async start() {
    this.host.listenTo(
      this.host.TextToSpeechFeature.EVENTS.pause,
      this.onStopSpeech.bind(this)
    );
    this.host.listenTo(
      this.host.TextToSpeechFeature.EVENTS.stop,
      this.onStopSpeech.bind(this)
    );

    this.ghostSound = new Audio('./sounds/169968__klankbeeld__horror-ghost-16.wav');

    await this.scene1();
    await this.scene2();
    await this.scene3();
    await this.scene4();
  }
}

export default Score;