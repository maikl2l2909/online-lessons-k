import { useMutation, useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { useState } from 'react';
import { api, type Course, type Lesson } from '../lib/api';
import { VideoPlayer } from '../components/VideoPlayer';

export function LessonPage() {
  const { slug, lessonId } = useParams<{ slug: string; lessonId: string }>();
  const [streamUrl, setStreamUrl] = useState<string | null>(null);
  const [poster, setPoster] = useState<string | null>(null);

  const { data: course } = useQuery({
    queryKey: ['course', slug],
    queryFn: async () => (await api.get(`/courses/${slug}`)).data.data as Course,
    enabled: !!slug,
  });

  const { data: lesson, isLoading } = useQuery({
    queryKey: ['lesson', lessonId],
    queryFn: async () => {
      const res = await api.get(`/lessons/${lessonId}`);
      const lessonData = res.data.data as Lesson;

      if (lessonData.video?.id) {
        const signed = await api.get(`/videos/${lessonData.video.id}/signed-url`);
        setStreamUrl(signed.data.stream_url);
        setPoster(signed.data.thumbnail_url);
      }

      return lessonData;
    },
    enabled: !!lessonId,
  });

  const markComplete = useMutation({
    mutationFn: () => api.post(`/lessons/${lessonId}/complete`),
  });

  const updateProgress = useMutation({
    mutationFn: (seconds: number) =>
      api.post(`/lessons/${lessonId}/progress`, { watched_seconds: seconds }),
  });

  if (isLoading || !lesson || !course) {
    return <div className="p-8 text-center text-slate-400">Loading lesson...</div>;
  }

  const allLessons = course.sections?.flatMap((s) =>
    s.lessons.map((l) => ({ ...l, sectionTitle: s.title }))
  ) ?? [];

  return (
    <div className="mx-auto flex max-w-7xl gap-6 px-4 py-6">
      <aside className="hidden w-80 shrink-0 lg:block">
        <div className="sticky top-6 rounded-2xl border border-slate-800 bg-slate-900 p-4">
          <Link to={`/courses/${slug}`} className="text-sm text-indigo-400 hover:underline">
            ← {course.title}
          </Link>
          <ul className="mt-4 space-y-1 text-sm">
            {allLessons.map((l) => (
              <li key={l.id}>
                <Link
                  to={`/courses/${slug}/lessons/${l.id}`}
                  className={`block rounded-lg px-3 py-2 ${
                    l.id === lesson.id ? 'bg-indigo-600/20 text-indigo-300' : 'text-slate-400 hover:bg-slate-800'
                  }`}
                >
                  {l.progress?.completed && <span className="mr-1">✓</span>}
                  {l.title}
                </Link>
              </li>
            ))}
          </ul>
        </div>
      </aside>

      <div className="flex-1">
        {streamUrl ? (
          <VideoPlayer
            src={streamUrl}
            poster={poster}
            onProgress={(s) => updateProgress.mutate(s)}
          />
        ) : (
          <div className="flex aspect-video items-center justify-center rounded-xl bg-slate-900 text-slate-500">
            Video processing or unavailable
          </div>
        )}

        <div className="mt-6 flex items-start justify-between">
          <div>
            <h1 className="text-2xl font-bold text-white">{lesson.title}</h1>
            <p className="mt-2 text-slate-400">{lesson.description}</p>
          </div>
          <button
            onClick={() => markComplete.mutate()}
            disabled={markComplete.isPending || lesson.progress?.completed}
            className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium hover:bg-emerald-500 disabled:opacity-50"
          >
            {lesson.progress?.completed ? 'Completed' : 'Mark complete'}
          </button>
        </div>
      </div>
    </div>
  );
}
