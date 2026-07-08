import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { api, type Course, type Lesson } from '../lib/api';
import { VideoPlayer } from '../components/VideoPlayer';

function formatDuration(seconds: number | null | undefined): string | null {
  if (!seconds || seconds <= 0) return null;
  const m = Math.floor(seconds / 60);
  const s = Math.floor(seconds % 60);
  return `${m}:${s.toString().padStart(2, '0')}`;
}

export function LessonPage() {
  const { slug, lessonId } = useParams<{ slug: string; lessonId: string }>();
  const queryClient = useQueryClient();
  const [streamUrl, setStreamUrl] = useState<string | null>(null);
  const [poster, setPoster] = useState<string | null>(null);

  const { data: course } = useQuery({
    queryKey: ['course', slug],
    queryFn: async () => (await api.get(`/courses/${slug}`)).data.data as Course,
    enabled: !!slug,
  });

  const { data: lesson, isLoading, isError, error } = useQuery({
    queryKey: ['lesson', lessonId],
    queryFn: async () => {
      const res = await api.get(`/lessons/${lessonId}`);
      const lessonData = res.data.data as Lesson;

      if (lessonData.video?.id) {
        const signed = await api.get(`/videos/${lessonData.video.id}/signed-url`);
        setStreamUrl(signed.data.stream_url);
        setPoster(signed.data.thumbnail_url);
      } else {
        setStreamUrl(null);
        setPoster(null);
      }

      return lessonData;
    },
    enabled: !!lessonId,
  });

  useEffect(() => {
    setStreamUrl(null);
    setPoster(null);
  }, [lessonId]);

  const markComplete = useMutation({
    mutationFn: () => api.post(`/lessons/${lessonId}/complete`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['lesson', lessonId] });
      queryClient.invalidateQueries({ queryKey: ['course', slug] });
    },
  });

  const updateProgress = useMutation({
    mutationFn: (seconds: number) =>
      api.post(`/lessons/${lessonId}/progress`, { watched_seconds: seconds }),
  });

  if (isLoading || (!isError && (!lesson || !course))) {
    return <div className="p-8 text-center text-slate-400">Loading lesson...</div>;
  }

  if (isError || !lesson) {
    const status = (error as { response?: { status?: number } })?.response?.status;
    return (
      <div className="mx-auto max-w-md px-4 py-20 text-center">
        <h1 className="text-xl font-semibold text-white">
          {status === 401 || status === 403 ? 'This lesson is locked' : 'Unable to load lesson'}
        </h1>
        <p className="mt-2 text-slate-400">
          {status === 401
            ? 'Please log in to watch this lesson.'
            : status === 403
              ? 'You need to purchase this course to watch this lesson.'
              : 'Something went wrong loading this lesson. Please try again.'}
        </p>
        <div className="mt-6 flex justify-center gap-3">
          {status === 401 && (
            <Link
              to="/login"
              className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
            >
              Log in
            </Link>
          )}
          <Link
            to={`/courses/${slug}`}
            className="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800"
          >
            Back to course
          </Link>
        </div>
      </div>
    );
  }

  if (!course) {
    return <div className="p-8 text-center text-slate-400">Loading lesson...</div>;
  }

  let counter = 0;
  const sections =
    course.sections?.map((section) => ({
      ...section,
      lessons: section.lessons.map((l) => ({ ...l, episodeNumber: ++counter })),
    })) ?? [];

  const currentEpisode =
    sections.flatMap((s) => s.lessons).find((l) => l.id === lesson.id)?.episodeNumber ?? null;

  return (
    <div className="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-6 lg:flex-row">
      {/* Sidebar with links to other videos */}
      <aside className="w-full shrink-0 lg:w-80">
        <div className="lg:sticky lg:top-6">
          <Link
            to={`/courses/${slug}`}
            className="inline-flex items-center gap-1 text-sm text-indigo-400 hover:underline"
          >
            ← {course.title}
          </Link>

          <nav className="mt-4 space-y-6">
            {sections.map((section) => (
              <div key={section.id}>
                <h3 className="mb-2 px-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {section.title}
                </h3>
                <ul className="space-y-1">
                  {section.lessons.map((l) => {
                    const isActive = l.id === lesson.id;
                    const isCompleted = l.progress?.completed;
                    const duration = formatDuration(l.duration_seconds);

                    const content = (
                      <>
                        <span
                          className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold ${
                            isCompleted
                              ? 'bg-emerald-600/20 text-emerald-400'
                              : isActive
                                ? 'bg-indigo-600 text-white'
                                : 'bg-slate-800 text-slate-400'
                          }`}
                        >
                          {isCompleted ? '✓' : l.episodeNumber}
                        </span>
                        <span className="min-w-0 flex-1 truncate">{l.title}</span>
                        {!l.can_view ? (
                          <span className="shrink-0 text-xs text-slate-600">🔒</span>
                        ) : (
                          duration && (
                            <span className="shrink-0 text-[11px] text-slate-600">{duration}</span>
                          )
                        )}
                      </>
                    );

                    const base =
                      'flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition';

                    return (
                      <li key={l.id}>
                        {l.can_view ? (
                          <Link
                            to={`/courses/${slug}/lessons/${l.id}`}
                            className={`${base} ${
                              isActive
                                ? 'bg-indigo-600/15 font-medium text-indigo-200'
                                : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200'
                            }`}
                          >
                            {content}
                          </Link>
                        ) : (
                          <div className={`${base} cursor-not-allowed text-slate-600`}>
                            {content}
                          </div>
                        )}
                      </li>
                    );
                  })}
                </ul>
              </div>
            ))}
          </nav>
        </div>
      </aside>

      {/* Main: video, name, description */}
      <div className="min-w-0 flex-1">
        {streamUrl ? (
          <VideoPlayer src={streamUrl} poster={poster} onProgress={(s) => updateProgress.mutate(s)} />
        ) : (
          <div className="flex aspect-video items-center justify-center rounded-xl bg-slate-900 text-slate-500">
            Video processing or unavailable
          </div>
        )}

        <div className="mt-6 flex items-start justify-between gap-4">
          <div className="min-w-0">
            <div className="flex items-center gap-3 text-sm text-slate-500">
              {currentEpisode && <span>Episode {currentEpisode}</span>}
              {formatDuration(lesson.duration_seconds) && (
                <>
                  <span>·</span>
                  <span>{formatDuration(lesson.duration_seconds)}</span>
                </>
              )}
            </div>
            <h1 className="mt-1 text-3xl font-bold text-white">{lesson.title}</h1>
          </div>

          <button
            onClick={() => markComplete.mutate()}
            disabled={markComplete.isPending || lesson.progress?.completed}
            className="shrink-0 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
          >
            {lesson.progress?.completed ? 'Completed' : 'Mark as complete'}
          </button>
        </div>

        {lesson.description && (
          <div className="mt-6 border-t border-slate-800 pt-6">
            <p className="whitespace-pre-line leading-relaxed text-slate-300">
              {lesson.description}
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
