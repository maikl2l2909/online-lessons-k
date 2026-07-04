import { Link } from 'react-router-dom';

export function HomePage() {
  return (
    <section className="mx-auto max-w-6xl px-4 py-20 text-center">
      <h1 className="text-5xl font-bold tracking-tight text-white">
        Learn from expert-led courses
      </h1>
      <p className="mx-auto mt-6 max-w-2xl text-lg text-slate-400">
        Stream HD lessons, track your progress, and unlock new skills with our adaptive video player.
      </p>
      <div className="mt-10 flex justify-center gap-4">
        <Link
          to="/courses"
          className="rounded-xl bg-indigo-600 px-6 py-3 font-medium hover:bg-indigo-500"
        >
          Browse courses
        </Link>
        <Link
          to="/register"
          className="rounded-xl border border-slate-700 px-6 py-3 font-medium hover:bg-slate-900"
        >
          Get started free
        </Link>
      </div>
    </section>
  );
}
