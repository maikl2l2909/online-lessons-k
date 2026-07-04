import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { api, type Course } from '../lib/api';

export function CoursesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['courses'],
    queryFn: async () => {
      const res = await api.get('/courses');
      return res.data.data as Course[];
    },
  });

  if (isLoading) {
    return <div className="p-8 text-center text-slate-400">Loading courses...</div>;
  }

  return (
    <div className="mx-auto max-w-6xl px-4 py-10">
      <h1 className="mb-8 text-3xl font-bold text-white">Course catalog</h1>
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {data?.map((course) => (
          <Link
            key={course.id}
            to={`/courses/${course.slug}`}
            className="group overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 transition hover:border-indigo-500/50"
          >
            <div className="aspect-video bg-slate-800">
              {course.thumbnail ? (
                <img src={course.thumbnail} alt="" className="h-full w-full object-cover" />
              ) : (
                <div className="flex h-full items-center justify-center text-slate-600">No thumbnail</div>
              )}
            </div>
            <div className="p-5">
              <h2 className="font-semibold text-white group-hover:text-indigo-300">{course.title}</h2>
              <p className="mt-2 line-clamp-2 text-sm text-slate-400">{course.description}</p>
              <div className="mt-4 flex items-center justify-between">
                <span className="font-medium text-indigo-400">{course.formatted_price}</span>
                <span className="text-xs text-slate-500">{course.lessons_count} lessons</span>
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
