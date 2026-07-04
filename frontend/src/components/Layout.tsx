import { Link, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export function Layout() {
  const { user, logout } = useAuth();

  return (
    <div className="min-h-screen">
      <header className="border-b border-slate-800 bg-slate-900/80 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
          <Link to="/" className="text-xl font-bold text-white">
            Course<span className="text-indigo-400">Hub</span>
          </Link>
          <nav className="flex items-center gap-4 text-sm">
            <Link to="/courses" className="text-slate-300 hover:text-white">Courses</Link>
            {user ? (
              <>
                <Link to="/dashboard" className="text-slate-300 hover:text-white">My Learning</Link>
                {user.role === 'admin' && (
                  <a href="/admin" className="text-slate-300 hover:text-white">Admin Panel</a>
                )}
                <button onClick={() => logout()} className="rounded-lg bg-slate-800 px-3 py-1.5 hover:bg-slate-700">
                  Logout
                </button>
              </>
            ) : (
              <>
                <Link to="/login" className="text-slate-300 hover:text-white">Login</Link>
                <Link to="/register" className="rounded-lg bg-indigo-600 px-3 py-1.5 hover:bg-indigo-500">
                  Sign up
                </Link>
              </>
            )}
          </nav>
        </div>
      </header>
      <main>
        <Outlet />
      </main>
    </div>
  );
}
