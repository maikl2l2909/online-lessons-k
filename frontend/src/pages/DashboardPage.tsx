import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { api, type Course } from '../lib/api';

export function DashboardPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['my-courses'],
    queryFn: async () => (await api.get('/my-courses')).data.data as Course[],
  });

  if (isLoading) {
    return <div className="p-8 text-center text-slate-400">Loading...</div>;
  }

  return (
    <div className="mx-auto max-w-6xl px-4 py-10">
      <h1 className="text-3xl font-bold text-white">My learning</h1>
      {data?.length === 0 ? (
        <p className="mt-8 text-slate-400">
          You haven't enrolled in any courses yet.{' '}
          <Link to="/courses" className="text-indigo-400">Browse courses</Link>
        </p>
      ) : (
        <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {data?.map((course) => (
            <Link
              key={course.id}
              to={`/courses/${course.slug}`}
              className="rounded-2xl border border-slate-800 bg-slate-900 p-5 hover:border-indigo-500/50"
            >
              <h2 className="font-semibold text-white">{course.title}</h2>
              <p className="mt-2 text-sm text-slate-400">{course.lessons_count} lessons</p>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
