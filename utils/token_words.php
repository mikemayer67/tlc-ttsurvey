<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$adj4_txt = [ 
  'able', 'aged', 'ajar', 'arid', 'back', 'bare', 'best', 'blue', 'bold', 'bony', 'both', 'busy', 'calm',
  'cold', 'cool', 'cute', 'damp', 'dark', 'dead', 'dear', 'deep', 'drab', 'dual', 'dull', 'each', 'easy',
  'even', 'evil', 'fair', 'fake', 'fast', 'fine', 'firm', 'flat', 'fond', 'free', 'full', 'glum', 'good',
  'gray', 'grim', 'half', 'hard', 'high', 'huge', 'icky', 'idle', 'keen', 'kind', 'lame', 'last', 'late',
  'lazy', 'lean', 'left', 'limp', 'live', 'lone', 'long', 'lost', 'loud', 'male', 'mean', 'meek', 'mild',
  'near', 'neat', 'next', 'nice', 'numb', 'oily', 'only', 'open', 'oval', 'pale', 'past', 'pink', 'poor',
  'posh', 'puny', 'pure', 'rare', 'rash', 'real', 'rich', 'ripe', 'rosy', 'rude', 'safe', 'same', 'sane',
  'sick', 'slim', 'slow', 'smug', 'soft', 'some', 'sore', 'sour', 'spry', 'tall', 'tame', 'tart', 'taut',
  'that', 'thin', 'this', 'tidy', 'tiny', 'torn', 'trim', 'true', 'twin', 'ugly', 'used', 'vain', 'vast',
  'warm', 'wary', 'wavy', 'weak', 'wide', 'wild', 'wiry', 'wise', 'worn', 'zany' ];

$noun6_txt = [
  'people', 'family', 'health', 'system', 'thanks', 'person', 'method', 'theory', 'nature', 'safety', 'player',
  'policy', 'series', 'camera', 'growth', 'income', 'energy', 'nation', 'moment', 'office', 'driver', 'flight',
  'length', 'dealer', 'member', 'advice', 'effort', 'wealth', 'county', 'estate', 'recipe', 'studio', 'agency',
  'memory', 'aspect', 'cancer', 'region', 'device', 'engine', 'height', 'sample', 'cousin', 'editor', 'extent',
  'guitar', 'leader', 'singer', 'tennis', 'basket', 'church', 'coffee', 'dinner', 'orange', 'poetry', 'police',
  'sector', 'volume', 'farmer', 'injury', 'speech', 'winner', 'worker', 'writer', 'breath', 'cookie', 'drawer',
  'insect', 'ladder', 'potato', 'sister', 'tongue', 'affair', 'client', 'throat', 'number', 'market', 'course',
  'school', 'amount', 'answer', 'matter', 'access', 'garden', 'reason', 'future', 'demand', 'action', 'record',
  'result', 'period', 'chance', 'figure', 'source', 'design', 'object', 'profit', 'inside', 'stress', 'review',
  'screen', 'medium', 'bottom', 'choice', 'impact', 'career', 'credit', 'square', 'effect', 'friend', 'couple',
  'debate', 'living', 'summer', 'button', 'desire', 'notice', 'damage', 'target', 'animal', 'author', 'budget',
  'ground', 'lesson', 'minute', 'bridge', 'letter', 'option', 'plenty', 'weight', 'factor', 'master', 'muscle',
  'appeal', 'mother', 'season', 'signal', 'spirit', 'street', 'status', 'ticket', 'degree', 'doctor', 'father',
  'stable', 'detail', 'shower', 'window', 'corner', 'finger', 'garage', 'manner', 'winter', 'battle', 'bother',
  'horror', 'phrase', 'relief', 'string', 'border', 'branch', 'breast', 'expert', 'league', 'native', 'parent',
  'salary', 'silver', 'tackle', 'assist', 'closet', 'collar', 'jacket', 'reward', 'bottle', 'candle', 'flower',
  'lawyer', 'mirror', 'purple', 'stroke', 'switch', 'bitter', 'carpet', 'island', 'priest', 'resort', 'scheme',
  'script', 'public', 'common', 'change', 'simple', 'second', 'single', 'travel', 'excuse', 'search', 'remove',
  'return', 'middle', 'charge', 'active', 'visual', 'affect', 'report', 'beyond', 'junior', 'unique', 'listen',
  'handle', 'finish', 'normal', 'secret', 'spread', 'spring', 'cancel', 'formal', 'remote', 'double', 'attack',
  'wonder', 'annual', 'nobody', 'repeat', 'divide', 'survey', 'escape', 'gather', 'repair', 'strike', 'employ',
  'mobile', 'senior', 'strain', 'yellow', 'permit', 'abroad', 'prompt', 'refuse', 'regret', 'reveal', 'female',
  'invite', 'resist', 'stupid' ];
