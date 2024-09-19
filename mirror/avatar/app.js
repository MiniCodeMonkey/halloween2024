import {Engine} from '@babylonjs/core/Engines/engine';
import {Color3, Vector3, Angle} from '@babylonjs/core/Maths/math';
import {DirectionalLight} from '@babylonjs/core/Lights/directionalLight';
import {ArcRotateCamera} from '@babylonjs/core/Cameras/arcRotateCamera';
import {ShadowGenerator} from '@babylonjs/core/Lights/Shadows/shadowGenerator';
import '@babylonjs/core/Helpers/sceneHelpers';
import '@babylonjs/core/Lights/Shadows/shadowGeneratorSceneComponent';

import {HostObject} from '@amazon-sumerian-hosts/babylon';
import {Scene} from '@babylonjs/core/scene';
import Score from './Score.js';

async function startShow(createScene) {
  const canvas = document.getElementById('renderCanvas');
  const engine = new Engine(canvas, true);
  const scene = await createScene(canvas);
  scene.render();
  engine.runRenderLoop(() => scene.render());
  window.addEventListener('resize', () => engine.resize());

  // Reveal the loaded scene.
  document.getElementById('mainScreen').classList.remove('loading');
}

function setupSceneEnvironment(scene) {
  // Create a simple environment.
  const environmentHelper = scene.createDefaultEnvironment({
    groundOpacity: 0,
    groundShadowLevel: 0.1,
  });
  environmentHelper.setMainColor(new Color3(0, 0, 0));

  scene.environmentIntensity = 1.2;

  const shadowLight = new DirectionalLight(
    'shadowLight',
    new Vector3(0.8, -2, -1)
  );
  shadowLight.diffuse = new Color3(1, 0.9, 0.62);
  shadowLight.intensity = 2;

  const keyLight = new DirectionalLight('keyLight', new Vector3(0.3, -1, -2));
  keyLight.diffuse = new Color3(1, 0.9, 0.65);
  keyLight.intensity = 3;

  // Add a camera.
  const cameraRotation = Angle.FromDegrees(85).radians();
  const cameraPitch = Angle.FromDegrees(70).radians();
  const camera = new ArcRotateCamera(
    'camera',
    cameraRotation,
    cameraPitch,
    2.6,
    new Vector3(0, 1.0, 0)
  );
  camera.wheelDeltaPercentage = 0.01;
  camera.minZ = 0.01;

  // Initialize user control of camera.
  const canvas = scene.getEngine().getRenderingCanvas();
  camera.attachControl(canvas, true);

  const shadowGenerator = new ShadowGenerator(2048, shadowLight);
  shadowGenerator.useBlurExponentialShadowMap = true;
  shadowGenerator.blurKernel = 8;
  scene.meshes.forEach(mesh => {
    shadowGenerator.addShadowCaster(mesh);
  });

  return {scene, shadowGenerator};
}

let host;
let scene;

const defaultGestureOptions = {
  holdTime: 1.5, // how long the gesture should last
  minimumInterval: 0, // how soon another gesture can be triggered
};

async function createScene() {

  scene = new Scene();
  scene.useRightHandedSystem = true;

  const {shadowGenerator} = setupSceneEnvironment(scene);
  initUi();

  // ===== Configure the AWS SDK =====
  const config = await (await fetch('/devConfig.json')).json();
  const cognitoIdentityPoolId = config.cognitoIdentityPoolId;

  AWS.config.region = cognitoIdentityPoolId.split(':')[0];
  AWS.config.credentials = new AWS.CognitoIdentityCredentials({
    IdentityPoolId: cognitoIdentityPoolId,
  });

  // ===== Instantiate the Sumerian Host =====

  // Edit the characterId if you would like to use one of
  // the other pre-built host characters. Available character IDs are:
  // "Cristine", "Fiona", "Grace", "Maya", "Jay", "Luke", "Preston", "Wes"
  const characterConfig = {
    modelUrl: './character-assets/characters/alien/alien.gltf',
    gestureConfigUrl: './character-assets/animations/alien/gesture.json',
    pointOfInterestConfigUrl: './character-assets/animations/alien/poi.json',
    animUrls: {
      animStandIdleUrl: './character-assets/animations/alien/stand_idle.glb',
      animLipSyncUrl: './character-assets/animations/alien/lipsync.glb',
      animGestureUrl: './character-assets/animations/alien/gesture.glb',
      animEmoteUrl: './character-assets/animations/alien/emote.glb',
      animFaceIdleUrl: './character-assets/animations/alien/face_idle.glb',
      animBlinkUrl: './character-assets/animations/alien/blink.glb',
      animPointOfInterestUrl: './character-assets/animations/alien/poi.glb',
    },
    lookJoint: 'char:gaze',
  };

  const pollyConfig = {pollyVoice: 'Mads', pollyEngine: 'standard'};
  host = await HostObject.createHost(scene, characterConfig, pollyConfig);

  // Tell the host to always look at the camera.
  host.PointOfInterestFeature.setTarget(scene.activeCamera);

  // Enable shadows.
  scene.meshes.forEach(mesh => {
    shadowGenerator.addShadowCaster(mesh);
  });

  const score = new Score(host);
  score.start();
  window.score = score;

  return scene;
}

function initUi() {
  // Register Gesture menu handlers.
  const gestureSelect = document.getElementById('gestureSelect');
  gestureSelect.addEventListener('change', evt =>
    playGesture(evt.target.value)
  );

  // Register Emote menu handlers.
  const emoteSelect = document.getElementById('emoteSelect');
  emoteSelect.addEventListener('change', evt => playEmote(evt.target.value));

  // Reveal the UI.
  //document.getElementById('uiPanel').classList.remove('hide');
  //document.getElementById('speakButton').onclick = speak.bind(this);
}

function playGesture(name) {
  if (!name) return;

  // This options object is optional. It's included here to demonstrate the available options.
  const gestureOptions = {
    holdTime: 1.5, // how long the gesture should last
    minimumInterval: 0, // how soon another gesture can be triggered
  };
  host.GestureFeature.playGesture('Gesture', name, gestureOptions);
}

function playEmote(name) {
  if (!name) return;

  host.GestureFeature.playGesture('Emote', name);
}

function speak() {
  const speech = document.getElementById('speechText').value;
  host.TextToSpeechFeature.play(speech);
}

startShow(createScene);
