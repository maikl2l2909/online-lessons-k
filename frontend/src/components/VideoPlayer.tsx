import { useEffect, useRef } from 'react';
import Hls from 'hls.js';

interface VideoPlayerProps {
  src: string;
  poster?: string | null;
  onProgress?: (seconds: number) => void;
}

export function VideoPlayer({ src, poster, onProgress }: VideoPlayerProps) {
  const videoRef = useRef<HTMLVideoElement>(null);

  useEffect(() => {
    const video = videoRef.current;
    if (!video || !src) return;

    let hls: Hls | null = null;

    if (Hls.isSupported()) {
      hls = new Hls();
      hls.loadSource(src);
      hls.attachMedia(video);
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
      video.src = src;
    }

    const handleTimeUpdate = () => {
      onProgress?.(Math.floor(video.currentTime));
    };

    video.addEventListener('timeupdate', handleTimeUpdate);

    return () => {
      video.removeEventListener('timeupdate', handleTimeUpdate);
      hls?.destroy();
    };
  }, [src, onProgress]);

  return (
    <video
      ref={videoRef}
      className="aspect-video w-full rounded-xl bg-black"
      controls
      poster={poster ?? undefined}
      playsInline
    />
  );
}
