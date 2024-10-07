import socket
import threading
import subprocess
import time
import os
import logging

# Set up logging
logging.basicConfig(level=logging.DEBUG, format='%(asctime)s - %(levelname)s - %(message)s')

# Configuration
HOST = '0.0.0.0'
PORT = 1337
DEFAULT_VIDEO = '/home/pi/Videos/Media Player Buffer Files/GA4_Buffer_Black_H.mp4'
SPECIAL_VIDEO = '/home/pi/Videos/Banshee_Startle Scare1_Holl_H.mp4'

class VideoPlayer:
    def __init__(self):
        self.current_video = DEFAULT_VIDEO
        self.process = None
        self.play_event = threading.Event()
        self.stop_event = threading.Event()

    def play_video(self, video_path, loop=False):
        logging.info(f"Attempting to play video: {video_path}, loop: {loop}")
        if self.process:
            self.process.terminate()
            self.process.wait()
        
        command = [
            'cvlc',
            '--no-xlib',
            '--no-osd',
            '--no-video-title-show',
            '--video-on-top',
            '--fullscreen',
        ]
        
        if loop:
            command.append('--loop')
        else:
            command.append('--play-and-exit')
        
        command.append(video_path)
        
        self.process = subprocess.Popen(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        logging.info("Video playback started")

    def run(self):
        while not self.stop_event.is_set():
            if self.play_event.is_set():
                logging.info("Special video playback triggered")
                self.play_event.clear()
                self.play_video(SPECIAL_VIDEO, loop=False)
                # Wait for the special video to finish
                while self.process and self.process.poll() is None:
                    time.sleep(0.1)
                logging.info("Special video playback completed")
            else:
                logging.info(f"Playing default video: {DEFAULT_VIDEO}")
                self.play_video(DEFAULT_VIDEO, loop=True)
            
            # Wait until play_event is set or stop_event is set
            while not self.play_event.is_set() and not self.stop_event.is_set():
                if self.process and self.process.poll() is not None:
                    # If the process has ended (for any reason), break the loop
                    break
                time.sleep(0.1)

    def play_special(self):
        self.play_event.set()

    def stop(self):
        self.stop_event.set()
        if self.process:
            self.process.terminate()
            self.process.wait()

def handle_client(conn, addr, video_player):
    logging.info(f"Connected by {addr}")
    video_player.play_special()
    conn.close()
    logging.info(f"Connection closed: {addr}")

def main():
    video_player = VideoPlayer()
    player_thread = threading.Thread(target=video_player.run)
    player_thread.start()

    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind((HOST, PORT))
        s.listen()
        logging.info(f"Server listening on {HOST}:{PORT}")
        
        try:
            while True:
                conn, addr = s.accept()
                logging.info(f"New connection: {addr}")
                client_thread = threading.Thread(target=handle_client, args=(conn, addr, video_player))
                client_thread.start()
        except KeyboardInterrupt:
            logging.info("Shutting down server...")
        finally:
            video_player.stop()
            player_thread.join()
            logging.info("Server shut down complete")

if __name__ == "__main__":
    main()
