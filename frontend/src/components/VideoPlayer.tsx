import { useEffect, useRef } from 'react';
import Hls from 'hls.js';

interface VideoPlayerProps {
  src: string;
  poster?: string | null;
  onProgress?: (seconds: number) => void;
}

export function VideoPlayer({ src, poster, onProgress }: VideoPlayerProps) {
  const videoRef = useRef<HTMLVideoElement>(null);

  const onProgressRef = useRef(onProgress);

  useEffect(() => {
    onProgressRef.current = onProgress;
  }, [onProgress]);

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

    let lastReportedSecond = -1;

    const handleTimeUpdate = () => {
      const currentSecond = Math.floor(video.currentTime);
      // Report progress every 5 seconds
      if (currentSecond !== lastReportedSecond && currentSecond % 5 === 0) {
        lastReportedSecond = currentSecond;
        onProgressRef.current?.(currentSecond);
      }
    };

    video.addEventListener('timeupdate', handleTimeUpdate);

    return () => {
      video.removeEventListener('timeupdate', handleTimeUpdate);
      hls?.destroy();
    };
  }, [src]);

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
