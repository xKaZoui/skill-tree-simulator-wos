import test from 'node:test';
import assert from 'node:assert/strict';
import { encodeBuild, decodeBuild } from '../../resources/js/skill-tree/build-url.js';

test('encodes level and points sorted by skill id, skipping zeros', () => {
    assert.equal(encodeBuild({ level: 80, points: { 13: 3, 12: 5, 14: 0 } }), '80:12-5,13-3');
    assert.equal(encodeBuild({ level: 35, points: {} }), '35:');
});

test('decodes what it encodes', () => {
    const state = { level: 62, points: { 7: 1, 120: 5 } };
    assert.deepEqual(decodeBuild(encodeBuild(state)), state);
    assert.deepEqual(decodeBuild('35:'), { level: 35, points: {} });
});

test('rejects text that is not a build', () => {
    for (const text of ['', null, 'abc', '80', '80:12', '80:12-', ':12-5', '80:12-5,', '1000:1-1']) {
        assert.equal(decodeBuild(text), null, text);
    }
});
