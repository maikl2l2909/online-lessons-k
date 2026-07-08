import { useMutation, useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { api, type Course, type Lesson } from '../lib/api';
import { useAuth } from '../context/AuthContext';

type EpisodeLesson = Lesson & { sectionTitle: string; episodeNumber: number };

function formatDuration(seconds: number | null | undefined): string | null {
  if (!seconds || seconds <= 0) return null;
  const m = Math.floor(seconds / 60);
  const s = Math.floor(seconds % 60);
  return `${m}:${s.toString().padStart(2, '0')}`;
}

function formatRunTime(seconds: number): string {
  if (seconds <= 0) return '—';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

export function CourseDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const { user } = useAuth();

  const { data: course, isLoading } = useQuery({
    queryKey: ['course', slug],
    queryFn: async () => {
      const res = await api.get(`/courses/${slug}`);
      return res.data.data as Course;
    },
    enabled: !!slug,
  });

  const checkout = useMutation({
    mutationFn: async () => {
      const res = await api.post(`/checkout/${slug}`);
      window.location.href = res.data.checkout_url;
    },
  });

  if (isLoading || !course) {
    return <div className="p-8 text-center text-slate-400">Loading...</div>;
  }

  let counter = 0;
  const episodes: EpisodeLesson[] =
    course.sections?.flatMap((section) =>
      section.lessons.map((lesson) => ({
        ...lesson,
        sectionTitle: section.title,
        episodeNumber: ++counter,
      }))
    ) ?? [];

  const totalSeconds = episodes.reduce((acc, e) => acc + (e.duration_seconds ?? 0), 0);
  const completedCount = episodes.filter((e) => e.progress?.completed).length;
  const progressPct = episodes.length ? Math.round((completedCount / episodes.length) * 100) : 0;
  const firstViewable = episodes.find((e) => e.can_view);

  return (
    <div className="mx-auto max-w-6xl px-4 py-10">
      <Link to="/courses" className="text-sm text-indigo-400 hover:underline">
        ← All courses
      </Link>

      <div className="mt-4 grid gap-10 lg:grid-cols-3">
        {/* Main column */}
        <div className="lg:col-span-2">
          <h1 className="text-4xl font-bold text-white">{course.title}</h1>
          {course.description && (
            <p className="mt-4 leading-relaxed text-slate-400">{course.description}</p>
          )}

          <h2 className="mt-10 mb-4 text-lg font-semibold text-white">
            Course Episodes
            <span className="ml-2 text-sm font-normal text-slate-500">
              ({episodes.length})
            </span>
          </h2>

          <ol className="space-y-2">
            {episodes.map((lesson) => {
              const duration = formatDuration(lesson.duration_seconds);
              const isCompleted = lesson.progress?.completed;

              const inner = (
                <>
                  <div
                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold ${
                      isCompleted
                        ? 'bg-emerald-600/20 text-emerald-400'
                        : lesson.can_view
                          ? 'bg-indigo-600/20 text-indigo-300'
                          : 'bg-slate-800 text-slate-500'
                    }`}
                  >
                    {isCompleted ? '✓' : lesson.episodeNumber}
                  </div>

                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="truncate font-medium text-white">{lesson.title}</span>
                      {lesson.is_free_preview && (
                        <span className="rounded bg-emerald-500/10 px-1.5 py-0.5 text-[11px] font-medium text-emerald-400">
                          Free
                        </span>
                      )}
                      {!lesson.can_view && !lesson.is_free_preview && (
                        <span className="text-xs text-slate-500">🔒</span>
                      )}
                    </div>
                    {lesson.description && (
                      <p className="mt-0.5 line-clamp-2 text-sm text-slate-400">
                        {lesson.description}
                      </p>
                    )}
                  </div>

                  {duration && (
                    <span className="shrink-0 self-center text-xs text-slate-500">{duration}</span>
                  )}
                </>
              );

              const baseClass =
                'flex gap-4 rounded-xl border px-4 py-3 transition';

              return (
                <li key={lesson.id}>
                  {lesson.can_view ? (
                    <Link
                      to={`/courses/${slug}/lessons/${lesson.id}`}
                      className={`${baseClass} border-slate-800 bg-slate-900/50 hover:border-indigo-500/50 hover:bg-slate-900`}
                    >
                      {inner}
                    </Link>
                  ) : (
                    <div className={`${baseClass} border-slate-800/50 opacity-70`}>{inner}</div>
                  )}
                </li>
              );
            })}
          </ol>
        </div>

        {/* Sidebar */}
        <div className="h-fit lg:sticky lg:top-6">
          <div className="rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <div className="text-3xl font-bold text-white">{course.formatted_price}</div>

            {course.is_purchased ? (
              firstViewable ? (
                <Link
                  to={`/courses/${slug}/lessons/${firstViewable.id}`}
                  className="mt-6 block w-full rounded-xl bg-indigo-600 py-3 text-center font-medium text-white hover:bg-indigo-500"
                >
                  {completedCount > 0 ? 'Continue learning' : 'Start course'}
                </Link>
              ) : (
                <p className="mt-4 text-emerald-400">You own this course</p>
              )
            ) : (
              <button
                onClick={() => checkout.mutate()}
                disabled={!user || checkout.isPending}
                className="mt-6 w-full rounded-xl bg-indigo-600 py-3 font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
              >
                {!user ? 'Login to purchase' : checkout.isPending ? 'Redirecting...' : 'Buy now'}
              </button>
            )}

            <dl className="mt-6 space-y-3 border-t border-slate-800 pt-6 text-sm">
              <div className="flex items-center justify-between">
                <dt className="text-slate-400">Episodes</dt>
                <dd className="font-medium text-white">{episodes.length}</dd>
              </div>
              <div className="flex items-center justify-between">
                <dt className="text-slate-400">Run time</dt>
                <dd className="font-medium text-white">{formatRunTime(totalSeconds)}</dd>
              </div>
              <div className="flex items-center justify-between">
                <dt className="text-slate-400">Free previews</dt>
                <dd className="font-medium text-white">
                  {episodes.filter((e) => e.is_free_preview).length}
                </dd>
              </div>
            </dl>

            {course.is_purchased && episodes.length > 0 && (
              <div className="mt-6 border-t border-slate-800 pt-6">
                <div className="mb-2 flex items-center justify-between text-sm">
                  <span className="text-slate-400">Progress</span>
                  <span className="font-medium text-white">{progressPct}%</span>
                </div>
                <div className="h-2 overflow-hidden rounded-full bg-slate-800">
                  <div
                    className="h-full rounded-full bg-emerald-500 transition-all"
                    style={{ width: `${progressPct}%` }}
                  />
                </div>
                <p className="mt-2 text-xs text-slate-500">
                  {completedCount} of {episodes.length} completed
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
