import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { api, type Course } from '../lib/api';
import { useAuth } from '../context/AuthContext';

export function CourseDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const { user } = useAuth();
  const queryClient = useQueryClient();

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

  const firstLesson = course.sections?.flatMap((s) => s.lessons).find((l) => l.can_view);

  return (
    <div className="mx-auto max-w-6xl px-4 py-10">
      <div className="grid gap-10 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <h1 className="text-4xl font-bold text-white">{course.title}</h1>
          <p className="mt-4 text-slate-400">{course.description}</p>

          <div className="mt-10 space-y-6">
            {course.sections?.map((section) => (
              <div key={section.id}>
                <h3 className="mb-3 font-semibold text-white">{section.title}</h3>
                <ul className="space-y-2">
                  {section.lessons.map((lesson) => (
                    <li key={lesson.id}>
                      {lesson.can_view ? (
                        <Link
                          to={`/courses/${slug}/lessons/${lesson.id}`}
                          className="flex items-center justify-between rounded-lg border border-slate-800 px-4 py-3 hover:border-indigo-500/50"
                        >
                          <span>{lesson.title}</span>
                          {lesson.is_free_preview && (
                            <span className="text-xs text-emerald-400">Free preview</span>
                          )}
                        </Link>
                      ) : (
                        <div className="flex items-center justify-between rounded-lg border border-slate-800/50 px-4 py-3 text-slate-500">
                          <span>{lesson.title}</span>
                          <span className="text-xs">🔒 Locked</span>
                        </div>
                      )}
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-900 p-6 h-fit">
          <div className="text-3xl font-bold text-white">{course.formatted_price}</div>
          {course.is_purchased ? (
            firstLesson ? (
              <Link
                to={`/courses/${slug}/lessons/${firstLesson.id}`}
                className="mt-6 block w-full rounded-xl bg-indigo-600 py-3 text-center font-medium hover:bg-indigo-500"
              >
                Continue learning
              </Link>
            ) : (
              <p className="mt-4 text-emerald-400">You own this course</p>
            )
          ) : (
            <button
              onClick={() => checkout.mutate()}
              disabled={!user || checkout.isPending}
              className="mt-6 w-full rounded-xl bg-indigo-600 py-3 font-medium hover:bg-indigo-500 disabled:opacity-50"
            >
              {!user ? 'Login to purchase' : checkout.isPending ? 'Redirecting...' : 'Buy now'}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
