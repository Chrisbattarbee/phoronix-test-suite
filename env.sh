# Make sure to set CC, CXX ourselves
# Make sure to set PROFDATATOOL

export PTS_USER_PATH_OVERRIDE=/data/.phoronix-test-suite
export CC=/data/llvm-project/llvm/build-release/bin/clang
export CXX=/data/llvm-project/llvm/build-release/bin/clang++
export PROFDATATOOL=/data/llvm-project/llvm/build-release/bin/llvm-profdata
alias cc=/data/llvm-project/llvm/build-release/bin/clang

