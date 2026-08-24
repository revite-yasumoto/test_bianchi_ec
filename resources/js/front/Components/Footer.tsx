import { FOOTER_COLUMNS } from './NavMenu';
import { NavLink } from './NavLink';

export function Footer() {
    return (
        <footer className="mt-5 bg-brand-deep px-5 pt-10 pb-8 text-white">
            <div className="grid grid-cols-[repeat(auto-fit,minmax(160px,1fr))] gap-7">
                <div>
                    <p className="font-sans text-lg font-extrabold tracking-[.14em]">
                        Bianchi
                    </p>
                    <p className="mt-1 font-mono text-sm tracking-[.22em] opacity-60">
                        BICYCLE STORE — DEMO
                    </p>
                </div>
                {FOOTER_COLUMNS.map((column) => (
                    <div key={column.key}>
                        <p className="mb-2.5 text-sm font-bold opacity-60">
                            {column.heading}
                        </p>
                        <ul className="flex flex-col items-start gap-2">
                            {column.links.map((link) => (
                                <li key={link.key}>
                                    <NavLink
                                        item={link}
                                        className="text-[15px] text-white/85"
                                        currentClassName="text-white"
                                    />
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>
            <p className="mt-8 border-t border-white/15 pt-4 text-sm opacity-55">
                © 2026 Bianchi Demo Store. これはデモ用のダミーサイトです。
            </p>
        </footer>
    );
}
