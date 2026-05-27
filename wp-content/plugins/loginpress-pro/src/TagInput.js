import React, { useEffect, useMemo, useRef, useState } from 'react';

const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/i;
const domainRx = /^@?[a-z0-9-]+(\.[a-z0-9-]+)+$/i;

const splitBySeparator = (val, sep) => {
	if (!val) return [];
	if (Array.isArray(val)) return val.filter(Boolean).map(s => String(s).trim()).filter(Boolean);
	const s = String(val);
	if (sep === '\n') return s.split(/\r?\n/).map(t => t.trim()).filter(Boolean);
	if (sep === ', ' || sep === ',') return s.split(/,\s*/).map(t => t.trim()).filter(Boolean);
	return s.split(sep).map(t => t.trim()).filter(Boolean);
};

const joinBySeparator = (list, sep) => {
	if (!Array.isArray(list)) return '';
	if (sep === '\n') return list.join('\n');
	if (sep === ', ' || sep === ',') return list.join(', ');
	return list.join(sep);
};

const TagInput = ({
	ignoreValue = false,
	name,
	value,
	onChange,
	placeholder = '',
	tagsSeparator = ', ',
	tagType = 'text', // 'email' | 'domain' | 'text'
	maxTags,
	disabled
}) => {
	const [tags, setTags] = useState([]);
	const [input, setInput] = useState('');
	const inputRef = useRef(null);

	useEffect(() => {
		setTags(splitBySeparator(value, tagsSeparator));
	}, [value, tagsSeparator]);

	const validate = useMemo(() => {
		if (tagType === 'email') return (v) => emailRx.test(v);
		if (tagType === 'domain') return (v) => domainRx.test(v);
		return () => true;
	}, [tagType]);

	const commit = (val) => {
		const raw = String(val || '').trim();
		if (!raw) return;
		if (maxTags && tags.length >= maxTags) return;
		if (!validate(raw)) return;
		if (tags.includes(raw)) return;
		const next = [...tags, raw];
		setTags(next);
		if (typeof onChange === 'function') onChange(name, joinBySeparator(next, tagsSeparator));
		setInput('');
	};

	const removeAt = (i) => {
		const next = tags.filter((_, idx) => idx !== i);
		setTags(next);
		if (typeof onChange === 'function') onChange(name, joinBySeparator(next, tagsSeparator));
	};

	const onKeyDown = (e) => {
		if (disabled) return;
		if (e.key === 'Enter') {
			e.preventDefault();
			commit(input);
			return;
		}
		if ((tagsSeparator === ',' || tagsSeparator === ', ') && e.key === ',') {
			e.preventDefault();
			commit(input);
			return;
		}
	};

	const onBlur = () => {
		if (input) commit(input);
	};

	return (
		<div className={`lp-tags-input ${disabled ? 'is-disabled' : ''}`} onClick={() => inputRef.current?.focus()}>
			{tags.map((t, i) => (
				<span key={`${t}-${i}`} className="lp-tag">
					<span className="lp-tag-text">{t}</span>
					<button
						type="button"
						className="lp-tag-remove"
						onClick={(e) => { e.stopPropagation(); removeAt(i); }}
						aria-label="Remove"
						disabled={disabled}
					>
						<span className="dashicons dashicons-no-alt"></span>
					</button>
				</span>
			))}
			<input
				ref={inputRef}
				type="search"
				className="lp-tag-input"
				placeholder={tags.length === 0 ? placeholder : ''}
				value={input}
				autocomplete="off"
				data-lpignore={ignoreValue ? 'true' : 'false'}
				onChange={(e) => setInput(e.target.value)}
				onKeyDown={onKeyDown}
				onBlur={onBlur}
				disabled={disabled}
			/>
		</div>
	);
};

export default TagInput;